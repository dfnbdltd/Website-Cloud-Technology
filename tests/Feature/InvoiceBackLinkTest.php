<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The invoice page is reachable from several lists (My Store invoices,
 * payments, a domain card, the Manage page, a service page). Back must return
 * to whichever one opened it, never off-site.
 */
class InvoiceBackLinkTest extends TestCase
{
    use RefreshDatabase;

    public function testInvoiceBackReturnsToTheListThatOpenedIt()
    {
        $user = $this->user();
        $invoice = $this->paidInvoice($user);

        foreach (['/mystore/invoices', '/mystore/payments', '/mystore/domains/9', '/mystore/hosting'] as $origin) {
            $this->withSession($this->customerSession($user))
                ->get('/invoices/' . $invoice->id . '?back=' . urlencode($origin))
                ->assertOk()
                ->assertSee('href="' . $origin . '"', false);
        }
    }

    public function testInvoiceBackFallsBackToTheInvoicesListWithoutAParameter()
    {
        $user = $this->user();
        $invoice = $this->paidInvoice($user);

        $this->withSession($this->customerSession($user))
            ->get('/invoices/' . $invoice->id)
            ->assertOk()
            ->assertSee('href="' . route('invoices') . '"', false);
    }

    public function testInvoiceBackRefusesToLeaveTheSite()
    {
        $user = $this->user();
        $invoice = $this->paidInvoice($user);

        $hostile = [
            'https://evil.example.com/phish',
            '//evil.example.com',
            '/\\evil.example.com',
            'javascript:alert(1)',
            '/' . "\t" . 'evil',
        ];

        foreach ($hostile as $back) {
            $this->withSession($this->customerSession($user))
                ->get('/invoices/' . $invoice->id . '?back=' . urlencode($back))
                ->assertOk()
                ->assertSee('href="' . route('invoices') . '"', false)
                ->assertDontSee('evil.example.com', false)
                ->assertDontSee('javascript:alert', false);
        }
    }

    public function testStoreListsCarryTheOriginWhenTheyLinkToAnInvoice()
    {
        $user = $this->user();
        $this->paidInvoice($user);

        $this->withSession($this->customerSession($user))
            ->get('/mystore/invoices')
            ->assertOk()
            ->assertSee('?back=' . urlencode('/mystore/invoices'), false);

        $this->withSession($this->customerSession($user))
            ->get('/mystore/payments')
            ->assertOk()
            ->assertSee('?back=' . urlencode('/mystore/payments'), false);
    }

    public function testNestedBackParametersAreNotChained()
    {
        $user = $this->user();
        $invoice = $this->paidInvoice($user);

        // An incoming ?back= must never end up inside the outgoing one.
        $this->withSession($this->customerSession($user))
            ->get('/mystore/invoices?back=' . urlencode('/mystore/payments'))
            ->assertOk()
            ->assertSee('?back=' . urlencode('/mystore/invoices'), false)
            ->assertDontSee(urlencode('back=%2Fmystore%2Fpayments'), false);
    }

    private function user()
    {
        return User::create([
            'name' => 'Back Link Tester',
            'email' => 'backlink-' . uniqid() . '@example.test',
            'password' => Hash::make('SecretP@ss1'),
            'email_verified_at' => now(),
        ]);
    }

    private function customerSession(User $user)
    {
        return ['customer' => [
            'id' => $user->id,
            'uid' => 'CT-000002',
            'name' => $user->name,
            'initials' => 'BL',
            'email' => $user->email,
            'phone' => '01700000000',
            'balance' => 0,
            'logo' => null,
        ]];
    }

    private function paidInvoice(User $user)
    {
        $order = DomainOrder::create([
            'reference' => 'BACKLINK-ORDER-' . uniqid(),
            'user_id' => $user->id,
            'total' => 1200,
            'status' => 'paid',
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'provider' => 'bkash',
            'customer_name' => $user->name,
            'customer_phone' => '01700000000',
            'customer_email' => $user->email,
        ]);

        $order->items()->create([
            'domain' => 'backlink.com',
            'tld' => 'com',
            'years' => 1,
            'price' => 1200,
            'renewal_price' => 1200,
        ]);

        $invoice = app(InvoiceService::class)->createForOrder($order->fresh());
        $invoice->forceFill([
            'status' => Invoice::STATUS_PAID,
            'amount_paid' => $invoice->total,
            'paid_at' => now(),
        ])->save();

        return $invoice->fresh();
    }
}
