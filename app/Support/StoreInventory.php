<?php

namespace App\Support;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Topup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Everything a customer owns, grouped the way My Store lists it.
 *
 * One source of truth for the seven My Store sections so the All Items
 * overview and the per-section detail pages always agree:
 *
 *  domains   paid domain orders (an order only owns a domain once it is paid)
 *  hosting   paid hosting invoice lines
 *  ssl       paid SSL invoice lines
 *  email     paid business-email invoice lines
 *  sms       paid SMS invoice lines
 *  invoices  paid domain orders + their invoice document
 *  payments  one ledger: wallet top-ups + domain order payments
 *
 * Hosting, SSL, business email and SMS have no order table yet, so their
 * billable documents are invoice lines: anything sold with a matching
 * item_type shows up here automatically once those checkouts ship.
 */
class StoreInventory
{
    /** @var int */
    private $userId;

    /** @var \Illuminate\Support\Collection|null */
    private $paidOrders;

    /** @var \Illuminate\Support\Collection|null */
    private $invoiceMap;

    public function __construct($userId)
    {
        $this->userId = (int) $userId;
    }

    /**
     * The seven sections, in the serial order used everywhere (sidebar,
     * overview cards, detail pages).
     *
     * @return array
     */
    public static function sections()
    {
        return [
            'domains' => [
                'key' => 'domains',
                'label' => 'Domain',
                'title' => 'Domains',
                'kicker' => 'MY DOMAIN',
                'icon' => '◎',
                'tone' => 'blue',
                'nav' => 'my-domains',
                'url' => ['mystore.domains', []],
                'blurb' => 'Registered names, renewal price and the day each one expires.',
                'empty_short' => 'No domains yet.',
                'empty' => 'No domains yet. Register your first domain and it will show up here with its order, invoice and payment details.',
                'cta' => ['label' => 'Register a domain', 'route' => 'domains.index', 'params' => []],
            ],
            'hosting' => [
                'key' => 'hosting',
                'label' => 'Web Hosting',
                'title' => 'Web Hosting',
                'kicker' => 'MY HOSTING SERVICES',
                'icon' => '▤',
                'tone' => 'green',
                'nav' => 'hosting',
                'url' => ['mystore.hosting', []],
                'blurb' => 'Shared, VPS and dedicated plans with their renewal dates.',
                'empty_short' => 'No hosting services yet.',
                'empty' => 'No hosting services yet. Your shared, VPS or dedicated plan will appear here with its invoice and renewal date after your next purchase.',
                'cta' => ['label' => 'Explore hosting plans', 'route' => 'client.hosting', 'params' => ['type' => 'shared']],
            ],
            'ssl' => [
                'key' => 'ssl',
                'label' => 'SSL Certificate',
                'title' => 'SSL Certificate',
                'kicker' => 'SSL CERTIFICATES',
                'icon' => '◇',
                'tone' => 'purple',
                'nav' => 'ssl',
                'url' => ['mystore.service', ['service' => 'ssl']],
                'blurb' => 'Certificates that keep your visitors on a secure connection.',
                'empty_short' => 'No certificates yet.',
                'empty' => 'No SSL certificates yet. Once an SSL order is placed it will appear here with its validity period and invoice.',
                'cta' => ['label' => 'SSL plans are coming soon', 'route' => null, 'params' => []],
            ],
            'email' => [
                'key' => 'email',
                'label' => 'Business Email',
                'title' => 'Business Email',
                'kicker' => 'BUSINESS EMAIL',
                'icon' => '✉',
                'tone' => 'orange',
                'nav' => 'email',
                'url' => ['mystore.service', ['service' => 'email']],
                'blurb' => 'Mailboxes on your own domain, with seats and renewal dates.',
                'empty_short' => 'No mailboxes yet.',
                'empty' => 'No business email mailboxes yet. Mailboxes you order will appear here with their seats, term and invoice.',
                'cta' => ['label' => 'Business email is coming soon', 'route' => null, 'params' => []],
            ],
            'sms' => [
                'key' => 'sms',
                'label' => 'SMS Service',
                'title' => 'SMS Service',
                'kicker' => 'SMS SERVICE',
                'icon' => '◌',
                'tone' => 'teal',
                'nav' => 'sms',
                'url' => ['mystore.service', ['service' => 'sms']],
                'blurb' => 'Bulk SMS credit for alerts, OTPs and campaigns.',
                'empty_short' => 'No SMS credit yet.',
                'empty' => 'No SMS service yet. Your sender ID and credit balance will appear here after your first top-up.',
                'cta' => ['label' => 'SMS service is coming soon', 'route' => null, 'params' => []],
            ],
            'invoices' => [
                'key' => 'invoices',
                'label' => 'Invoices',
                'title' => 'Invoices',
                'kicker' => 'INVOICES',
                'icon' => '▤',
                'tone' => 'slate',
                'nav' => 'invoices',
                'url' => ['mystore.invoices', []],
                'blurb' => 'Every billable document, with its status and PDF.',
                'empty_short' => 'No invoices yet.',
                'empty' => 'No invoices yet. A document is created automatically for each of your orders and top-ups.',
                'cta' => ['label' => 'Browse domains', 'route' => 'domains.index', 'params' => []],
            ],
            'payments' => [
                'key' => 'payments',
                'label' => 'Payments',
                'title' => 'Payments',
                'kicker' => 'PAYMENTS',
                'icon' => '৳',
                'tone' => 'gold',
                'nav' => 'payments',
                'url' => ['mystore.payments', []],
                'blurb' => 'Wallet top-ups and order payments in one ledger.',
                'empty_short' => 'No payments yet.',
                'empty' => 'No payments yet. Wallet top-ups and order payments land here as soon as they are made.',
                'cta' => ['label' => 'Add balance with bKash', 'route' => 'wallet.index', 'params' => []],
            ],
        ];
    }

    /**
     * One section's metadata ('domains', 'hosting', 'ssl', 'email', 'sms',
     * 'invoices', 'payments').
     *
     * @param  string  $key
     * @return array|null
     */
    public static function section($key)
    {
        $sections = static::sections();

        return isset($sections[$key]) ? $sections[$key] : null;
    }

    /**
     * Paid domain orders — the only orders that represent owned domains.
     *
     * @return \Illuminate\Support\Collection
     */
    public function paidOrders()
    {
        if ($this->paidOrders === null) {
            $this->paidOrders = DomainOrder::with('items')
                ->where('user_id', $this->userId)
                ->where('payment_status', 'paid')
                ->latest()
                ->get();
        }

        return $this->paidOrders;
    }

    public function domainCount()
    {
        return $this->paidOrders()->count();
    }

    public function paidToDate()
    {
        return round((float) $this->paidOrders()->sum('total'), 2);
    }

    public function pendingTopupCount()
    {
        return Topup::where('user_id', $this->userId)->where('status', 'pending')->count();
    }

    /**
     * Invoice document per paid order, keyed by order id.
     *
     * @return \Illuminate\Support\Collection
     */
    public function invoiceMap()
    {
        if ($this->invoiceMap === null) {
            $this->invoiceMap = collect();

            if (Schema::hasTable('invoices')) {
                $orderIds = $this->paidOrders()->modelKeys();

                if ($orderIds) {
                    $this->invoiceMap = Invoice::whereIn('source_id', $orderIds)
                        ->where('source_type', DomainOrder::class)
                        ->get()
                        ->keyBy('source_id');
                }
            }
        }

        return $this->invoiceMap;
    }

    /**
     * Owned domains, one row per domain in a paid order.
     *
     * @return \Illuminate\Support\Collection
     */
    public function domains()
    {
        return $this->paidOrders()->flatMap(function (DomainOrder $order) {
            return $order->items->map(function ($item) use ($order) {
                $years = max(1, (int) $item->years);
                $registered = $order->created_at;
                $expires = $registered ? $registered->copy()->addYears($years) : null;

                return [
                    'item_id' => $item->id,
                    'domain' => $item->domain,
                    'tld' => $item->tld,
                    'registration_status' => $item->registration_status,
                    'years' => $years,
                    'price' => (float) $item->price,
                    'renewal_price' => (float) $item->renewal_price,
                    'order' => $order,
                    'invoice' => $this->invoiceMap()->get($order->id),
                    'registered_at' => $registered,
                    'expires_at' => $expires,
                    'days_left' => $expires
                        ? (int) now()->startOfDay()->diffInDays($expires->copy()->startOfDay(), false)
                        : null,
                    'payments' => $this->orderPayments($order->id),
                ];
            });
        })->values();
    }

    /**
     * Payment attempts recorded against one domain order.
     *
     * @param  int  $orderId
     * @return \Illuminate\Support\Collection
     */
    public function orderPayments($orderId)
    {
        if (!Schema::hasTable('payments')) {
            return collect();
        }

        return Payment::where('domain_order_id', $orderId)->latest()->get();
    }

    /**
     * Owned services that are billed through invoice lines (hosting, SSL,
     * business email, SMS).
     *
     * @param  string  $key
     * @return \Illuminate\Support\Collection
     */
    public function service($key)
    {
        $prefixes = [
            'hosting' => ['hosting'],
            'ssl' => ['ssl', 'certificate'],
            'email' => ['email', 'mailbox'],
            'sms' => ['sms'],
        ];

        if (empty($prefixes[$key]) || !Schema::hasTable('invoices') || !Schema::hasTable('invoice_items')) {
            return collect();
        }

        $rows = collect();

        foreach ($this->paidInvoices() as $invoice) {
            foreach ($invoice->items as $item) {
                $type = strtolower(trim((string) $item->item_type));
                if ($type === '' || !$this->matchesAnyPrefix($type, $prefixes[$key])) {
                    continue;
                }

                $years = (int) $item->years;
                $started = $invoice->paid_at ?: $invoice->created_at;

                $rows->push([
                    'name' => $item->description,
                    'item_type' => $type,
                    'years' => $years,
                    'quantity' => max(1, (int) $item->quantity),
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                    'invoice' => $invoice,
                    'started_at' => $started,
                    'expires_at' => $years > 0 && $started ? $started->copy()->addYears($years) : null,
                ]);
            }
        }

        return $rows->values();
    }

    /**
     * Paid invoices for the customer, newest first, with their line items.
     *
     * @return \Illuminate\Support\Collection
     */
    private function paidInvoices()
    {
        if (!Schema::hasTable('invoices')) {
            return collect();
        }

        return Invoice::with('items')
            ->where('user_id', $this->userId)
            ->where('status', Invoice::STATUS_PAID)
            ->latest('paid_at')
            ->get();
    }

    /**
     * The payments ledger: wallet top-ups and domain order payments, newest
     * first, each row carrying its invoice when one exists.
     *
     * @return \Illuminate\Support\Collection
     */
    public function payments()
    {
        $rows = [];

        foreach (Topup::where('user_id', $this->userId)->latest()->get() as $topup) {
            $rows[] = [
                'kind' => 'topup',
                'reference' => $topup->reference,
                'description' => 'Wallet top-up',
                'method' => (string) $topup->method,
                'amount' => (float) $topup->amount,
                'status' => (string) $topup->status,
                'at' => $topup->created_at,
                'invoice' => $this->invoiceFor(Topup::class, $topup->id),
                'order' => null,
                'order_url' => null,
            ];
        }

        $orders = $this->paidOrders()->keyBy('id');

        if ($orders->isNotEmpty() && Schema::hasTable('payments')) {
            foreach (Payment::whereIn('domain_order_id', $orders->keys()->all())->latest()->get() as $payment) {
                $order = $orders->get($payment->domain_order_id);
                $rows[] = [
                    'kind' => 'order',
                    'reference' => $payment->reference,
                    'description' => $order ? 'Domain order · ' . $order->reference : 'Domain order',
                    'method' => (string) $payment->provider,
                    'amount' => (float) $payment->amount,
                    'status' => (string) $payment->status,
                    'at' => $payment->created_at,
                    'invoice' => $order ? $this->invoiceMap()->get($order->id) : null,
                    'order' => $order,
                    'order_url' => $order ? route('domains.confirmation', $order->reference) : null,
                ];
            }
        }

        // A plain collection: rows are arrays, and Eloquent's merge() would
        // call getKey() on them.
        return collect($rows)
            ->sortByDesc(function ($row) {
                return $row['at'] ? $row['at']->timestamp : 0;
            })
            ->values();
    }

    /**
     * Totals for the Payments page header.
     *
     * @return array{received: float, pending: float, count: int}
     */
    public function paymentTotals()
    {
        $rows = $this->payments();

        return [
            'received' => round((float) $rows->whereIn('status', ['approved', 'paid'])->sum('amount'), 2),
            'pending' => round((float) $rows->whereIn('status', ['pending', 'initiated'])->sum('amount'), 2),
            'count' => $rows->count(),
        ];
    }

    /**
     * Row count per section, used by the overview cards.
     *
     * @return array
     */
    public function counts()
    {
        return [
            'domains' => $this->domains()->count(),
            'hosting' => $this->service('hosting')->count(),
            'ssl' => $this->service('ssl')->count(),
            'email' => $this->service('email')->count(),
            'sms' => $this->service('sms')->count(),
            'invoices' => $this->paidOrders()->count(),
            'payments' => $this->payments()->count(),
        ];
    }

    /**
     * @param  string  $value
     * @param  array  $prefixes
     * @return bool
     */
    private function matchesAnyPrefix($value, array $prefixes)
    {
        foreach ($prefixes as $prefix) {
            if (strpos($value, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string  $type
     * @param  int  $id
     * @return \App\Models\Invoice|null
     */
    private function invoiceFor($type, $id)
    {
        if (!Schema::hasTable('invoices')) {
            return null;
        }

        return Invoice::where('source_type', $type)->where('source_id', $id)->first();
    }
}
