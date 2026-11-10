<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Topup;
use App\Services\InvoiceService;
use App\Services\ResellerClubClient;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * My Store always lists all seven sections; each one has its own page that
 * shows the full detail of whatever it holds, or a first-purchase prompt.
 */
class MyStorePagesTest extends TestCase
{
    use RefreshDatabase;

    /** The overview plus the seven section pages. */
    private const STORE_PAGES = [
        '/mystore',
        '/mystore/domains',
        '/mystore/hosting',
        '/mystore/ssl',
        '/mystore/email',
        '/mystore/sms',
        '/mystore/invoices',
        '/mystore/payments',
    ];

    public function testGuestsAreSentToLoginFromEveryStorePage()
    {
        foreach (self::STORE_PAGES as $uri) {
            $this->get($uri)->assertRedirect(route('login', ['return_to' => $uri]));
        }
    }

    public function testOverviewListsAllSevenSectionsEvenWhenNothingIsOwned()
    {
        $response = $this->withSession($this->customerSession($this->user()))->get('/mystore');

        $response->assertOk()->assertSee('All Items');

        foreach (['domains', 'hosting', 'ssl', 'email', 'sms', 'invoices', 'payments'] as $key) {
            $response->assertSee('id="card-' . $key . '"', false);
        }

        $response->assertSee('No domains yet.')
            ->assertSee('No hosting services yet.')
            ->assertSee('No certificates yet.')
            ->assertSee('No mailboxes yet.')
            ->assertSee('No SMS credit yet.')
            ->assertSee('No invoices yet.')
            ->assertSee('No payments yet.');

        // Balance cards belong to the overview.
        $response->assertSee('AVAILABLE BALANCE')->assertSee('PAID TO DATE');
    }

    public function testOverviewFillsTheCardsThatHoldSomething()
    {
        $user = $this->user();
        $order = $this->paidOrder($user, 'MYSTORE-ORDER', 'mystore-domain.test');
        Topup::create([
            'user_id' => $user->id,
            'reference' => 'MYSTORE-TOPUP',
            'amount' => 500,
            'method' => 'bkash',
            'status' => 'approved',
        ]);

        $response = $this->withSession($this->customerSession($user))->get('/mystore');

        $response->assertOk()
            ->assertSee('mystore-domain.test')
            ->assertSee($order->reference)
            ->assertSee('MYSTORE-TOPUP')
            ->assertDontSee('No domains yet.');
    }

    public function testDomainPageShowsTheFullDetailOfEveryDomain()
    {
        $user = $this->user();
        $order = $this->paidOrder($user, 'DETAIL-ORDER', 'detail-domain.test');
        $invoice = Invoice::where('source_id', $order->id)
            ->where('source_type', DomainOrder::class)
            ->firstOrFail();

        $response = $this->withSession($this->customerSession($user))->get('/mystore/domains');

        $response->assertOk()
            ->assertSee('detail-domain.test')
            ->assertSee('REGISTERED')
            ->assertSee('EXPIRES')
            ->assertSee('RENEWAL PRICE')
            ->assertSee($order->reference)
            ->assertSee($invoice->invoice_number)
            ->assertSee('DETAIL-ORDER-PAY');
    }

    public function testDomainOrganizationConsentCanBeAcceptedOrCancelled()
    {
        $user = $this->user();
        $order = $this->paidOrder($user, 'CONSENT-ORDER', 'consent-domain.test');
        $order->update(['status' => 'active']);
        $item = $order->items()->first();
        $item->update(['registration_status' => 'registered']);
        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('domainOrderId')->twice()->with('consent-domain.test')->andReturn('127547539');
        $resellerClub->shouldReceive('modifyRegistrantOrganizationConsent')->once()
            ->with('consent-domain.test', '127547539', true)->andReturn(['status' => 'SUCCESS']);
        $resellerClub->shouldReceive('modifyRegistrantOrganizationConsent')->once()
            ->with('consent-domain.test', '127547539', false)->andReturn(['status' => 'SUCCESS']);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->withSession($this->customerSession($user))
            ->post("/mystore/domains/{$item->id}/organization-consent", ['consent' => '1'])
            ->assertRedirect(route('mystore.domains.details', $item));

        $this->withSession($this->customerSession($user))
            ->post("/mystore/domains/{$item->id}/organization-consent", ['consent' => '0'])
            ->assertRedirect(route('mystore.domains.details', $item));
    }

    public function testDomainPageListsPaidTransferRequestsSeparatelyFromOwnedDomains()
    {
        $user = $this->user();
        $order = DomainOrder::create([
            'reference' => 'TRANSFER-STORE-ORDER',
            'user_id' => $user->id,
            'total' => 1600,
            'status' => 'transfer_pending',
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'provider' => 'bkash',
            'customer_name' => $user->name,
            'customer_phone' => '01700000000',
            'customer_email' => $user->email,
        ]);
        $order->items()->create([
            'domain' => 'transfer-domain.com',
            'tld' => 'com',
            'years' => 1,
            'price' => 1600,
            'renewal_price' => 1600,
            'order_type' => 'transfer',
            'registration_status' => 'transfer_submitted',
        ]);
        app(InvoiceService::class)->createForOrder($order->fresh());

        $session = $this->customerSession($user);
        $this->withSession($session)->get('/mystore/domains')
            ->assertOk()
            ->assertSee('transfer-domain.com')
            ->assertSee('Pending')
            ->assertSee('accepted by the registrar')
            ->assertSee($order->reference)
            ->assertDontSee('No domains yet.');

        $this->withSession($session)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('transfer-domain.com')
            ->assertSee('Pending')
            ->assertSee('accepted by the registrar');

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get(route('admin.orders'))
            ->assertOk()
            ->assertSee('transfer-domain.com transfer: Pending')
            ->assertSee('Transfer Initialized')
            ->assertSee('Success');

        $this->withSession($session)->get(route('domains.confirmation', $order->reference))
            ->assertOk()
            ->assertSee('Your payment is confirmed and the transfer request has been submitted');
    }

    public function testUnbuiltSectionsShowTheirEmptyStateAndFirstPurchasePrompt()
    {
        $session = $this->customerSession($this->user());

        $expectations = [
            '/mystore/hosting' => ['No hosting services yet.', 'Explore hosting plans'],
            '/mystore/ssl' => ['No SSL certificates yet.'],
            '/mystore/email' => ['No business email mailboxes yet.'],
            '/mystore/sms' => ['No SMS service yet.'],
            '/mystore/invoices' => ['No invoices yet.'],
            '/mystore/payments' => ['No payments yet.'],
        ];

        foreach ($expectations as $uri => $needles) {
            $response = $this->withSession($session)->get($uri);
            $response->assertOk()->assertSee('All Items');

            foreach ($needles as $needle) {
                $response->assertSee($needle);
            }
        }
    }

    /** Hosting / SSL / email / SMS read their owned rows from paid invoice lines. */
    public function testServicePagesReadPaidInvoiceLines()
    {
        $user = $this->user();
        $this->paidInvoiceLine($user, 'hosting', 'Shared hosting — starter plan', 2400);

        $session = $this->customerSession($user);

        $this->withSession($session)->get('/mystore/hosting')
            ->assertOk()
            ->assertSee('Shared hosting — starter plan')
            ->assertSee('৳ 2,400.00')
            ->assertDontSee('No hosting services yet.');

        $this->withSession($session)->get('/mystore/ssl')
            ->assertOk()
            ->assertSee('No SSL certificates yet.');
    }

    public function testInvoicesPageListsPaidOrdersWithTheirInvoiceLinks()
    {
        $user = $this->user();
        $order = $this->paidOrder($user, 'INVOICE-ORDER', 'invoice-domain.test');
        $invoice = Invoice::where('source_id', $order->id)
            ->where('source_type', DomainOrder::class)
            ->firstOrFail();

        $this->withSession($this->customerSession($user))->get('/mystore/invoices')
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee($invoice->invoice_number)
            ->assertSee(route('invoices.view', $invoice), false);
    }

    public function testPaymentsPageMergesTopupsAndOrderPayments()
    {
        $user = $this->user();
        $this->paidOrder($user, 'LEDGER-ORDER', 'ledger-domain.test');
        Topup::create([
            'user_id' => $user->id,
            'reference' => 'LEDGER-TOPUP',
            'amount' => 500,
            'method' => 'bkash',
            'status' => 'approved',
        ]);

        $this->withSession($this->customerSession($user))->get('/mystore/payments')
            ->assertOk()
            ->assertSee('LEDGER-TOPUP')
            ->assertSee('LEDGER-ORDER-PAY')
            ->assertSee('RECEIVED')
            ->assertSee('Wallet top-up');
    }

    /** A buyer who paid an order directly (no wallet top-up) must still see it. */
    public function testPaymentsLedgerWorksWithoutAnyTopUp()
    {
        $user = $this->user();
        $this->paidOrder($user, 'NOTOPUP-ORDER', 'notopup-domain.test');

        $response = $this->withSession($this->customerSession($user))
            ->get('/mystore/payments')
            ->assertOk()
            ->assertSee('NOTOPUP-ORDER-PAY')
            ->assertDontSee('No payments yet.');

        // The overview card reads the same ledger.
        $this->withSession($this->customerSession($user))
            ->get('/mystore')
            ->assertOk()
            ->assertSee('NOTOPUP-ORDER-PAY');
    }

    public function testUnknownServiceSlugIsNotFound()
    {
        $this->withSession($this->customerSession($this->user()))
            ->get('/mystore/not-a-service')
            ->assertNotFound();
    }

    private function user()
    {
        return User::create([
            'name' => 'Store Tester',
            'email' => 'store-' . uniqid() . '@example.test',
            'password' => Hash::make('SecretP@ss1'),
            'email_verified_at' => now(),
        ]);
    }

    private function customerSession(User $user)
    {
        return ['customer' => [
            'id' => $user->id,
            'uid' => 'CT-000001',
            'name' => $user->name,
            'initials' => 'ST',
            'email' => $user->email,
            'phone' => '01700000000',
            'balance' => 0,
            'logo' => null,
            'plan' => 'BDIX Premium',
        ]];
    }

    private function paidOrder(User $user, $reference, $domain)
    {
        $order = DomainOrder::create([
            'reference' => $reference,
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
            'domain' => $domain,
            'tld' => 'test',
            'years' => 2,
            'price' => 600,
            'renewal_price' => 700,
            'purchase_price' => 500,
        ]);
        Payment::create([
            'domain_order_id' => $order->id,
            'reference' => $reference . '-PAY',
            'provider' => 'bkash',
            'amount' => 1200,
            'status' => 'paid',
        ]);

        app(InvoiceService::class)->createForOrder($order->fresh());

        return $order->fresh();
    }

    private function paidInvoiceLine(User $user, $itemType, $description, $amount)
    {
        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateNumber(),
            'user_id' => $user->id,
            'type' => Invoice::TYPE_ORDER,
            'status' => Invoice::STATUS_PAID,
            'payment_method' => 'bkash',
            'amount_paid' => $amount,
            'currency' => 'BDT',
            'subtotal' => $amount,
            'total' => $amount,
            'paid_at' => now(),
            'created_by' => 'system',
        ]);

        $invoice->items()->create([
            'sl_no' => 1,
            'description' => $description,
            'item_type' => $itemType,
            'years' => 1,
            'quantity' => 1,
            'unit_price' => $amount,
            'line_total' => $amount,
        ]);

        return $invoice;
    }
}
