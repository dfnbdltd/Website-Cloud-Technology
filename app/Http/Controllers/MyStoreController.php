<?php

namespace App\Http\Controllers;

use App\Models\DomainOrderItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\DomainTransferSyncService;
use App\Services\ResellerClubClient;
use App\Support\DomainActivity;
use App\Support\StoreInventory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * My Store: the All Items overview plus one page per section.
 *
 * All seven sections are always reachable — owning something only decides
 * whether a section renders its rows or its first-purchase prompt.
 */
class MyStoreController extends Controller
{
    private $resellerClub;

    public function __construct(ResellerClubClient $resellerClub)
    {
        $this->resellerClub = $resellerClub;
    }

    /**
     * ResellerClub finishes a transfer days after we submit it. When the customer opens
     * My Store, re-check THEIR pending transfers so a finished one shows as Success and
     * gets its Manage button without anyone clicking anything (and without cron).
     *
     * Throttled (once per 2 minutes per customer), a no-op when nothing is pending or
     * ResellerClub is not configured, and never allowed to break the page.
     */
    private function refreshPendingTransfers(Request $request)
    {
        try {
            $userId = (int) ($request->user() ? $request->user()->id : $request->session()->get('customer.id'));
            if ($userId > 0) {
                $sync = app(DomainTransferSyncService::class);
                $sync->syncPendingForUser($userId);
                $sync->backfillExpiry($userId);
            }
        } catch (\Throwable $exception) {
            \Log::warning('Transfer status refresh failed', ['error' => $exception->getMessage()]);
        }
    }

    /**
     * All Items: the balance row and the seven section cards, in serial order.
     */
    public function index(Request $request)
    {
        $this->refreshPendingTransfers($request);

        $inventory = $this->inventory($request);

        $items = [
            'domains' => $inventory->domains(),
            'hosting' => $inventory->service('hosting'),
            'ssl' => $inventory->service('ssl'),
            'email' => $inventory->service('email'),
            'sms' => $inventory->service('sms'),
            'invoices' => $inventory->paidOrders(),
            'payments' => $inventory->payments(),
        ];

        return view('store.index', [
            'sections' => StoreInventory::sections(),
            'items' => $items,
            'summaries' => $this->summaries($items),
            'paidTotal' => $inventory->paidToDate(),
            'domainCount' => $inventory->domainCount(),
            'pendingTopup' => $inventory->pendingTopupCount(),
        ]);
    }

    /**
     * Domains: every owned name with its order, expiry and payment history.
     */
    public function domains(Request $request)
    {
        $this->refreshPendingTransfers($request);

        $inventory = $this->inventory($request);

        return view('store.domains', [
            'section' => StoreInventory::section('domains'),
            'domains' => $inventory->domains(),
            'transfers' => $inventory->transfers(),
        ]);
    }

    public function domainDetails(Request $request, $itemId)
    {
        $item = $this->ownedDomainItem($request, $itemId);
        $invoice = Invoice::where('source_type', \App\Models\DomainOrder::class)
            ->where('source_id', $item->order->id)
            ->first();

        // This domain's own ResellerClub order, resolved once and reused below.
        $resellerOrderId = $this->resellerOrderId($item);

        // Opening Manage always reads the whole activity history from ResellerClub
        // (never a copy up to a minute old). The fallback copy is kept for outages.
        if ($resellerOrderId !== null) {
            ResellerClubClient::forgetDomainActionsCache($resellerOrderId);
        }

        // A transfer carries the expiry ResellerClub reported when it finished;
        // if that was never stored (e.g. an admin marked it complete by hand),
        // read it once now and keep it.
        if ($item->isTransfer() && $item->expires_at === null && $resellerOrderId !== null && !$this->isTestDomain($item->domain)) {
            $details = $this->resellerClub->domainDetails($resellerOrderId);
            $expiresAt = is_array($details) && isset($details['endtime']) ? DomainActivity::timestamp($details['endtime']) : null;
            if ($expiresAt) {
                $item->update(['expires_at' => Carbon::createFromTimestamp($expiresAt)]);
            }
        }

        $nameservers = $this->resellerClub->nameservers($resellerOrderId);
        if (!$nameservers) {
            $nameservers = array_values(array_filter(array_map('trim', (array) config('services.resellerclub.nameservers', []))));
        }

        $activity = $this->registrarActivity($item, $resellerOrderId);

        return view('store.domain-details', [
            'item' => $item,
            'order' => $item->order,
            'invoice' => $invoice,
            'latestActivities' => $activity['items']->take(3)->values(),
            'activitiesUnavailable' => !$activity['available'],
            'nameservers' => $nameservers,
            'isTransfer' => $item->isTransfer(),
        ]);
    }

    public function domainActivity(Request $request, $itemId)
    {
        $item = $this->ownedDomainItem($request, $itemId);
        $order = $item->order;
        $invoice = Invoice::where('source_type', \App\Models\DomainOrder::class)
            ->where('source_id', $order->id)
            ->first();
        $resellerOrderId = $this->resellerOrderId($item);
        if ($resellerOrderId !== null) {
            ResellerClubClient::forgetDomainActionsCache($resellerOrderId);
        }
        $activity = $this->registrarActivity($item, $resellerOrderId);

        return view('store.domain-activity', [
            'item' => $item,
            'activities' => $activity['items'],
            'activitiesUnavailable' => !$activity['available'],
            'orderUrl' => route('domains.confirmation', $order->reference),
            'orderHistory' => $this->orderHistory($order, $invoice, $item->isTransfer()),
            'isTransfer' => $item->isTransfer(),
        ]);
    }

    public function updateNameservers(Request $request, $itemId)
    {
        $userId = (int) ($request->user()
            ? $request->user()->id
            : $request->session()->get('customer.id'));
        $item = DomainOrderItem::manageable()
            ->whereKey($itemId)
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();

        abort_if(isset(config('services.test_domains')[$item->domain]), 404);

        $rawNameservers = $request->input('nameservers');
        $rawNameservers = is_string($rawNameservers) ? trim($rawNameservers) : '';
        $nameserverInput = $rawNameservers;
        $nameservers = array_values(array_filter(array_map(function ($nameserver) {
            return strtolower(trim($nameserver));
        }, preg_split('/\r\n|\r|\n/', $rawNameservers)), function ($nameserver) {
            return $nameserver !== '';
        }));

        $request->merge(['nameservers' => $nameservers]);
        $validator = Validator::make($request->only('nameservers'), [
            'nameservers' => ['required', 'array', 'min:2', 'max:13'],
            'nameservers.*' => [
                'required',
                'string',
                'max:253',
                'regex:/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i',
                'distinct',
            ],
        ], [
            'nameservers.min' => 'Enter at least two nameservers, one per line.',
            'nameservers.max' => 'Enter no more than 13 nameservers.',
            'nameservers.*.regex' => 'That is not a valid nameserver hostname. Use a format like ns1.example.com',
            'nameservers.*.distinct' => 'Each nameserver must be unique.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('mystore.domains.details', $item)
                ->withErrors($validator)
                ->withInput(['nameservers' => $nameserverInput])
                ->with('manage_domain_item', $item->id);
        }

        $resellerClubOrderId = $this->resellerClub->domainOrderId($item->domain);
        if ($resellerClubOrderId === null) {
            \Log::warning('ResellerClub nameserver update skipped because the domain order ID could not be resolved', [
                'domain' => $item->domain,
                'user_id' => $userId,
            ]);

            return redirect()->route('mystore.domains.details', $item)
                ->withErrors(['nameservers' => 'Cloud Technology unable to update nameservers. Please check correct nameserver and try again.'])
                ->withInput(['nameservers' => $nameserverInput])
                ->with('manage_domain_item', $item->id);
        }

        $response = $this->resellerClub->modifyNameservers($resellerClubOrderId, $nameservers);
        if (!is_array($response) || strtoupper((string) ($response['status'] ?? '')) !== 'SUCCESS') {
            \Log::warning('ResellerClub nameserver update did not succeed', [
                'domain' => $item->domain,
                'user_id' => $userId,
                'response' => $response,
            ]);

            return redirect()->route('mystore.domains.details', $item)
                ->withErrors(['nameservers' => $this->nameserverUpdateError($response)])
                ->withInput(['nameservers' => $nameserverInput])
                ->with('manage_domain_item', $item->id);
        }

        // ResellerClub has just logged this change; make Latest Activity re-read it.
        ResellerClubClient::forgetDomainActions($resellerClubOrderId);

        return redirect()->route('mystore.domains.details', $item)
            ->with('success', 'Nameserver updated successfully')
            ->with('manage_domain_item', $item->id);
    }

    public function updateOrganizationConsent(Request $request, $itemId)
    {
        $userId = (int) ($request->user()
            ? $request->user()->id
            : $request->session()->get('customer.id'));
        $item = $this->ownedDomainItem($request, $itemId);

        // The question is answered once. When it already was (a second tab, a repeated
        // post), ResellerClub is not called again and the page simply opens without the box.
        if ($item->hasAnsweredOrganizationConsent()) {
            return redirect()->route('mystore.domains.details', $item);
        }

        $consent = $request->input('consent');
        if (!in_array($consent, ['1', '0', 1, 0, true, false], true)) {
            return redirect()->route('mystore.domains.details', $item)
                ->withErrors(['consent' => 'Choose Yes, I agree or Cancel.']);
        }

        abort_if(isset(config('services.test_domains')[$item->domain]), 404);

        $orderId = $this->resellerClub->domainOrderId($item->domain);
        $response = $this->resellerClub->modifyRegistrantOrganizationConsent(
            $item->domain,
            $orderId,
            filter_var($consent, FILTER_VALIDATE_BOOLEAN)
        );
        if (!is_array($response) || strtoupper((string) ($response['status'] ?? '')) !== 'SUCCESS') {
            \Log::warning('ResellerClub organization consent update did not succeed', [
                'domain' => $item->domain,
                'user_id' => $userId,
                'response' => $response,
            ]);

            return redirect()->route('mystore.domains.details', $item)
                ->withErrors(['consent' => $this->providerErrorMessage($response) ?: 'The organization consent could not be updated. Please contact support.']);
        }

        if (is_scalar($orderId)) {
            ResellerClubClient::forgetDomainActions($orderId);
        }

        // ResellerClub accepted the answer: remember it so the consent box is gone for good.
        // (Only a successful answer is stored; a failed call leaves the box in place.)
        $accepted = filter_var($consent, FILTER_VALIDATE_BOOLEAN);
        $item->update([
            'org_consent_choice' => $accepted ? 'accepted' : 'declined',
            'org_consent_responded_at' => now(),
        ]);

        return redirect()->route('mystore.domains.details', $item)
            ->with('consent_success', $accepted
                ? 'Organization publication consent accepted.'
                : 'Organization publication consent cancelled.');
    }

    private function nameserverUpdateError($response)
    {
        $providerMessage = $this->providerErrorMessage($response);

        return $providerMessage
            ? 'Cloud Technology Detects : ' . $providerMessage
            : 'Cloud Technology was unable to update nameservers. Please check the nameservers and try again.';
    }

    private function providerErrorMessage($response)
    {
        if (is_scalar($response)) {
            return $this->cleanProviderMessage((string) $response);
        }

        if (!is_array($response)) {
            return null;
        }

        foreach (['message', 'error_description', 'error', 'description', 'details'] as $key) {
            if (!array_key_exists($key, $response)) {
                continue;
            }

            $message = $response[$key];
            if (is_array($message)) {
                $message = $message['message'] ?? $message['description'] ?? null;
            }

            if (is_scalar($message)) {
                $message = $this->cleanProviderMessage((string) $message);
                if ($message) {
                    return $message;
                }
            }
        }

        return null;
    }

    private function cleanProviderMessage($message)
    {
        $message = html_entity_decode(strip_tags($message), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $message = preg_replace('/[\r\n\t ]+/', ' ', trim($message));
        $message = preg_replace('/\b(api[-_ ]?key|auth(?:orization)?[-_ ]?code|access[-_ ]?token|token|password)\s*[:=]\s*\S+/i', '$1=[redacted]', $message);
        $message = trim(substr($message, 0, 500));

        return $message && !in_array(strtoupper($message), ['SUCCESS', 'OK', 'ERROR', 'FAILED', 'FAILURE'], true)
            ? $message
            : null;
    }

    public function hosting(Request $request)
    {
        return $this->servicePage($request, 'hosting');
    }

    /**
     * SSL / business email / SMS share one page shape: billable invoice lines.
     */
    public function service(Request $request, $service)
    {
        abort_unless(in_array($service, ['ssl', 'email', 'sms'], true), 404);

        return $this->servicePage($request, $service);
    }

    public function invoices(Request $request)
    {
        $inventory = $this->inventory($request);

        $hostingOrders = $inventory->paidHostingOrders();

        return view('store.invoices', [
            'section' => StoreInventory::section('invoices'),
            'orders' => $inventory->paidOrders(),
            'invoiceMap' => $inventory->invoiceMap(),
            'hostingOrders' => $hostingOrders,
            'hostingInvoiceMap' => $inventory->hostingInvoiceMap($hostingOrders),
        ]);
    }

    public function payments(Request $request)
    {
        $inventory = $this->inventory($request);

        return view('store.payments', [
            'section' => StoreInventory::section('payments'),
            'payments' => $inventory->payments(),
            'totals' => $inventory->paymentTotals(),
        ]);
    }

    private function servicePage(Request $request, $key)
    {
        $inventory = $this->inventory($request);

        return view('store.service', [
            'section' => StoreInventory::section($key),
            'items' => $inventory->service($key),
        ]);
    }

    private function inventory(Request $request)
    {
        return new StoreInventory((int) $request->session()->get('customer.id'));
    }

    private function ownedDomainItem(Request $request, $itemId)
    {
        $userId = (int) ($request->user()
            ? $request->user()->id
            : $request->session()->get('customer.id'));

        // Registrations and finished transfers are managed the same way.
        return DomainOrderItem::manageable()
            ->with('order')
            ->whereKey($itemId)
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();
    }

    /**
     * "Check status" on a pending transfer: ask ResellerClub now instead of
     * waiting for the scheduler. Only the owner's own submitted transfer.
     */
    public function syncTransfer(Request $request, $itemId)
    {
        $userId = (int) ($request->user()
            ? $request->user()->id
            : $request->session()->get('customer.id'));

        $item = DomainOrderItem::with('order')
            ->whereKey($itemId)
            ->where('order_type', 'transfer')
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId)->where('payment_status', 'paid');
            })
            ->firstOrFail();

        if ($item->registration_status === 'transferred') {
            return redirect()->route('mystore.domains')->with('success', $item->domain . ' has been transferred to your account.');
        }

        $outcome = app(DomainTransferSyncService::class)->syncItem($item);

        $messages = [
            DomainTransferSyncService::OUTCOME_COMPLETED => $item->domain . ' has been transferred to your account. You can manage it now.',
            DomainTransferSyncService::OUTCOME_FAILED => 'The transfer of ' . $item->domain . ' did not complete. Please contact support with your order reference.',
            DomainTransferSyncService::OUTCOME_PENDING => 'The transfer of ' . $item->domain . ' is still in progress at the registrar. This usually takes 5–7 days.',
            DomainTransferSyncService::OUTCOME_UNAVAILABLE => 'We could not reach the registrar just now. Please try again shortly.',
            DomainTransferSyncService::OUTCOME_SKIPPED => 'This transfer is already up to date.',
        ];

        $redirect = redirect()->route('mystore.domains');

        return $outcome === DomainTransferSyncService::OUTCOME_FAILED || $outcome === DomainTransferSyncService::OUTCOME_UNAVAILABLE
            ? $redirect->withErrors(['transfer' => $messages[$outcome]])
            : $redirect->with('success', $messages[$outcome]);
    }

    /**
     * The ResellerClub order ID of THIS domain item.
     *
     * Stored on the item the first time it is resolved, so later page views
     * skip the lookup. Never derived from another item of the same cart order.
     * Returns null for demo/test domains and when ResellerClub cannot be asked.
     *
     * @return string|null
     */
    private function resellerOrderId(DomainOrderItem $item)
    {
        if ($this->isTestDomain($item->domain)) {
            return null;
        }

        $stored = trim((string) $item->reseller_order_id);
        if ($stored !== '' && ctype_digit($stored)) {
            return $stored;
        }

        $orderId = $this->resellerClub->domainOrderId($item->domain);
        if ($orderId !== null && $orderId !== $stored) {
            $item->update(['reseller_order_id' => $orderId]);
        }

        return $orderId;
    }

    private function isTestDomain($domain)
    {
        $testDomains = (array) config('services.test_domains', []);

        return isset($testDomains[$domain]);
    }

    /**
     * ResellerClub's own activity list for one domain, shaped for the views.
     *
     * 'available' is false when the activity could not be read, so the page
     * can say so instead of claiming the domain has no activity.
     *
     * @param  string|null  $resellerOrderId
     * @return array  ['available' => bool, 'items' => \Illuminate\Support\Collection]
     */
    private function registrarActivity(DomainOrderItem $item, $resellerOrderId)
    {
        if ($this->isTestDomain($item->domain)) {
            return ['available' => true, 'items' => collect()];
        }

        $actions = $resellerOrderId === null ? null : $this->resellerClub->domainActions($resellerOrderId);
        if ($actions === null) {
            return ['available' => false, 'items' => collect()];
        }

        $items = collect($actions)->map(function ($action) {
            return [
                'id' => $action['id'],
                'title' => $action['title'],
                'description' => $action['description'],
                'state' => $action['state'],
                'state_label' => $action['state_label'],
                'at' => $this->activityDate($action['at']),
                'started_at' => $this->activityDate($action['started_at'] ?: $action['added_at']),
                'completed_at' => $this->activityDate($action['completed_at']),
            ];
        })->values();

        return ['available' => true, 'items' => $items];
    }

    /**
     * Our own record of the order (placed, paid, invoiced), newest first.
     * Shown under the ResellerClub timeline on the full activity page.
     */
    private function orderHistory($order, $invoice, $isTransfer = false)
    {
        $history = collect();
        $orderLink = $invoice
            ? route('invoices.view', $invoice)
            : route('domains.confirmation', $order->reference);
        $orderLinkLabel = $invoice ? 'View Invoice' : 'View Order';

        if ($order->created_at) {
            $history->push([
                'at' => $order->created_at,
                'title' => $isTransfer ? 'Domain transfer ordered' : 'Domain order placed',
                'description' => 'Order ' . $order->reference,
                'link' => $orderLink,
                'link_label' => $orderLinkLabel,
            ]);
        }

        foreach (Payment::where('domain_order_id', $order->id)->latest()->get() as $payment) {
            $history->push([
                'at' => $payment->created_at,
                'title' => 'Payment ' . ucfirst((string) $payment->status),
                'description' => ucfirst((string) $payment->provider ?: 'Payment') . ' · ' . $payment->reference . ' · ৳ ' . number_format((float) $payment->amount, 2),
                'link' => $orderLink,
                'link_label' => $orderLinkLabel,
            ]);
        }

        if ($invoice && $invoice->created_at) {
            $history->push([
                'at' => $invoice->created_at,
                'title' => 'Invoice issued',
                'description' => $invoice->invoice_number . ' · ' . ucfirst((string) $invoice->status),
                'link' => route('invoices.view', $invoice),
                'link_label' => 'View Invoice',
            ]);
        }

        return $history->sortByDesc(function ($entry) {
            return $entry['at'] ? $entry['at']->timestamp : 0;
        })->values();
    }

    /**
     * @param  int|null  $timestamp  Unix seconds
     * @return \Carbon\Carbon|null
     */
    private function activityDate($timestamp)
    {
        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }

    /**
     * One-line "what's inside" summary per overview card; null when the
     * section is empty (the card shows its empty state instead).
     *
     * @param  array  $items
     * @return array
     */
    private function summaries(array $items)
    {
        $domains = $items['domains'];

        $next = $domains
            ->filter(function ($domain) {
                return $domain['expires_at'] !== null;
            })
            ->sortBy(function ($domain) {
                return $domain['expires_at']->timestamp;
            })
            ->first();

        return [
            'domains' => $domains->isEmpty()
                ? null
                : ($next ? 'Next renewal ' . $next['expires_at']->format('d M Y') : 'Registered in your account'),
            'hosting' => $items['hosting']->isEmpty() ? null : 'Active hosting services',
            'ssl' => $items['ssl']->isEmpty() ? null : 'Certificates on file',
            'email' => $items['email']->isEmpty() ? null : 'Mailboxes on your domain',
            'sms' => $items['sms']->isEmpty() ? null : 'SMS credit in your account',
            'invoices' => $items['invoices']->isEmpty()
                ? null
                : '৳ ' . number_format((float) $items['invoices']->sum('total'), 2) . ' billed in total',
            'payments' => $items['payments']->isEmpty()
                ? null
                : '৳ ' . number_format((float) $items['payments']->whereIn('status', ['approved', 'paid'])->sum('amount'), 2) . ' received',
        ];
    }
}
