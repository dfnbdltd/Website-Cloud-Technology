<?php

namespace App\Http\Controllers;

use App\Models\DomainOrderItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ResellerClubClient;
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
     * All Items: the balance row and the seven section cards, in serial order.
     */
    public function index(Request $request)
    {
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

        $nameservers = $this->resellerClub->nameservers($this->resellerClub->domainOrderId($item->domain));
        if (!$nameservers) {
            $nameservers = array_values(array_filter(array_map('trim', (array) config('services.resellerclub.nameservers', []))));
        }

        return view('store.domain-details', [
            'item' => $item,
            'order' => $item->order,
            'invoice' => $invoice,
            'activities' => $this->domainActivities($item->order, $invoice),
            'nameservers' => $nameservers,
        ]);
    }

    public function domainActivity(Request $request, $itemId)
    {
        $item = $this->ownedDomainItem($request, $itemId);
        $order = $item->order;
        $invoice = Invoice::where('source_type', \App\Models\DomainOrder::class)
            ->where('source_id', $order->id)
            ->first();
        return view('store.domain-activity', [
            'item' => $item,
            'activities' => $this->domainActivities($order, $invoice),
        ]);
    }

    public function updateNameservers(Request $request, $itemId)
    {
        $userId = (int) ($request->user()
            ? $request->user()->id
            : $request->session()->get('customer.id'));
        $item = DomainOrderItem::whereKey($itemId)
            ->where('registration_status', 'registered')
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('payment_status', 'paid')
                    ->where('status', 'active');
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
            'nameservers.*.regex' => 'That is not a valid nameserver hostname. Use a format like ns1.example.com.',
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
                ->withErrors(['nameservers' => 'Cloud Technology unable to update nameservers. Please check correct nameserver and try again.'])
                ->withInput(['nameservers' => $nameserverInput])
                ->with('manage_domain_item', $item->id);
        }

        return redirect()->route('mystore.domains.details', $item)
            ->with('success', 'Nameserver updated successfully')
            ->with('manage_domain_item', $item->id);
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

        return view('store.invoices', [
            'section' => StoreInventory::section('invoices'),
            'orders' => $inventory->paidOrders(),
            'invoiceMap' => $inventory->invoiceMap(),
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

        return DomainOrderItem::with('order')
            ->whereKey($itemId)
            ->where('order_type', 'registration')
            ->where('registration_status', 'registered')
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('payment_status', 'paid')
                    ->where('status', 'active');
            })
            ->firstOrFail();
    }

    private function domainActivities($order, $invoice)
    {
        $activities = collect();
        $orderLink = $invoice
            ? route('invoices.view', $invoice)
            : route('domains.confirmation', $order->reference);
        $orderLinkLabel = $invoice ? 'View Invoice' : 'View Order';

        if ($order->created_at) {
            $activities->push([
                'at' => $order->created_at,
                'title' => 'Domain order placed',
                'description' => 'Order ' . $order->reference,
                'link' => $orderLink,
                'link_label' => $orderLinkLabel,
            ]);
        }

        foreach (Payment::where('domain_order_id', $order->id)->latest()->get() as $payment) {
            $activities->push([
                'at' => $payment->created_at,
                'title' => 'Payment ' . ucfirst((string) $payment->status),
                'description' => ucfirst((string) $payment->provider ?: 'Payment') . ' · ' . $payment->reference . ' · ৳ ' . number_format((float) $payment->amount, 2),
                'link' => $orderLink,
                'link_label' => $orderLinkLabel,
            ]);
        }

        if ($invoice && $invoice->created_at) {
            $activities->push([
                'at' => $invoice->created_at,
                'title' => 'Invoice issued',
                'description' => $invoice->invoice_number . ' · ' . ucfirst((string) $invoice->status),
                'link' => route('invoices.view', $invoice),
                'link_label' => 'View Invoice',
            ]);
        }

        $resellerOrderId = $this->resellerClub->domainOrderId($order->items()->value('domain'));
        foreach ($this->resellerClub->domainActions($resellerOrderId) as $action) {
            $at = $this->activityDate($action['actionCompleted'] ?? null)
                ?: $this->activityDate($action['actionStarted'] ?? null)
                ?: $this->activityDate($action['actionAdded'] ?? null);
            if (!$at) {
                continue;
            }
            $description = (string) ($action['actionStatusDesc'] ?? $action['description'] ?? 'ResellerClub domain action');
            $activities->push([
                'at' => $at,
                'title' => (string) ($action['actionTypeDesc'] ?? $action['actionType'] ?? 'Domain action'),
                'description' => $description,
                'link' => route('domains.confirmation', $order->reference),
                'link_label' => 'View Order',
            ]);
        }

        return $activities->sortByDesc(function ($activity) {
            return $activity['at'] ? $activity['at']->timestamp : 0;
        })->values();
    }

    private function activityDate($value)
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : Carbon::createFromTimestamp($timestamp);
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
