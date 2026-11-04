<?php

namespace App\Http\Controllers;

use App\Models\DomainOrderItem;
use App\Services\ResellerClubClient;
use App\Support\StoreInventory;
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
            'nameservers.*.regex' => 'Enter valid hostnames such as ns1.example.com.',
            'nameservers.*.distinct' => 'Each nameserver must be unique.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('mystore.domains')
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

            return redirect()->route('mystore.domains')
                ->withErrors(['nameservers' => 'ResellerClub could not find this domain order. Please contact support.'])
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

            return redirect()->route('mystore.domains')
                ->withErrors(['nameservers' => 'ResellerClub could not update these nameservers. Please check the values and try again.'])
                ->withInput(['nameservers' => $nameserverInput])
                ->with('manage_domain_item', $item->id);
        }

        return redirect()->route('mystore.domains')
            ->with('success', 'Nameservers for ' . $item->domain . ' were updated successfully.')
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
