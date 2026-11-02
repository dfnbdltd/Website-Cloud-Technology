<?php

namespace App\Http\Controllers;

use App\Support\StoreInventory;
use Illuminate\Http\Request;

/**
 * My Store: the All Items overview plus one page per section.
 *
 * All seven sections are always reachable — owning something only decides
 * whether a section renders its rows or its first-purchase prompt.
 */
class MyStoreController extends Controller
{
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
        ]);
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
