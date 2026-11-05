<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Topup;
use App\Services\InvoiceService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Invoice system: creation beside orders/top-ups, ownership guards for
 * customer view + PDF, and the admin-only edit flow (the 3-year -> 4-year
 * upgrade on a paid invoice reopens the difference as unpaid).
 */
class InvoiceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function customer($email = 'buyer@example.test')
    {
        return User::create([
            'name' => 'Test Buyer',
            'email' => $email,
            'password' => Hash::make('SecretP@ss1'),
            'email_verified_at' => now(),
        ]);
    }

    private function customerSession($user)
    {
        return ['customer' => [
            'id' => $user->id,
            'uid' => 'CT-000001',
            'name' => $user->name,
            'initials' => 'TB',
            'email' => $user->email,
            'phone' => '01700000000',
            'balance' => 0,
            'logo' => null,
            'plan' => 'BDIX Premium',
        ]];
    }

    private function orderWithItems($user, $years = 3, $price = 1214.00, $paid = false, $purchasePrice = null)
    {
        $order = DomainOrder::create([
            'reference' => 'CT-TEST-' . uniqid(),
            'user_id' => $user->id,
            'total' => $price * $years,
            'status' => $paid ? 'paid' : 'payment_pending',
            'payment_method' => 'nagad',
            'payment_status' => $paid ? 'paid' : 'pending',
            'provider' => 'manual',
            'customer_name' => $user->name,
            'customer_phone' => '01700000000',
            'customer_email' => $user->email,
        ]);
        $order->items()->create([
            'domain' => 'invoiceable.com',
            'tld' => 'com',
            'years' => $years,
            'price' => $price,
            'renewal_price' => $price,
            'purchase_price' => $purchasePrice,
        ]);

        return $order;
    }

    public function testInvoiceIsCreatedBesideADomainOrder()
    {
        $user = $this->customer();
        $order = $this->orderWithItems($user);

        $invoice = app(InvoiceService::class)->createForOrder($order->fresh());

        $this->assertNotNull($invoice);
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status);
        $this->assertSame(3642.0, $invoice->total);
        $this->assertSame('INV-000001', $invoice->invoice_number);

        $item = $invoice->items->first();
        $this->assertSame(1, $item->sl_no);
        $this->assertSame('invoiceable.com', $order->items->first()->domain);
        $this->assertSame('Domain registration — invoiceable.com', $item->description);
        $this->assertSame(3, $item->years);
        $this->assertSame(3642.0, $item->line_total);
        $this->assertNull($item->purchase_price);

        // Idempotent: calling again returns the same invoice, not a second one.
        $again = app(InvoiceService::class)->createForOrder($order->fresh());
        $this->assertSame($invoice->id, $again->id);
        $this->assertSame(1, Invoice::count());
    }

    public function testAdminMarkingOrderPaidFlipsTheInvoice()
    {
        $user = $this->customer();
        $order = $this->orderWithItems($user);
        $invoice = app(InvoiceService::class)->createForOrder($order->fresh());

        $this->withSession(['admin' => ['email' => 'admin@mail.net']])
            ->post('/admin/orders/' . $order->id . '/status', ['status' => 'paid']);

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame(3642.0, (float) $invoice->fresh()->amount_paid);
        $this->assertSame(0.0, $invoice->fresh()->balanceDue());
    }

    public function testCustomerCanViewAndDownloadOwnInvoicePdfOnly()
    {
        $user = $this->customer();
        $other = $this->customer('someone-else@example.test');

        $invoice = app(InvoiceService::class)->createForOrder($this->orderWithItems($other, 3, 1214.00, true)->fresh());

        $response = $this->withSession($this->customerSession($user))
            ->get(route('invoices.view', $invoice));
        $response->assertRedirect(route('invoices'));

        $this->withoutExceptionHandling();
        $own = app(InvoiceService::class)->createForOrder($this->orderWithItems($user, 3, 1214.00, true)->fresh());
        $session = $this->customerSession($user);

        $pdf = $this->withSession($session)->get(route('invoices.pdf', $own) . '?download=1');
        $pdf->assertStatus(200);
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringContainsString('INV-', (string) $pdf->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function testAdminEditExtendsYearsOnAPaidInvoiceAndReopensDifference()
    {
        $user = $this->customer();
        $order = $this->orderWithItems($user, 3, 1214.00, true, 1214); // 3 years @ 1214 = 3642
        $invoice = app(InvoiceService::class)->createForOrder($order->fresh());
        $invoice->forceFill([
            'status' => Invoice::STATUS_PAID,
            'amount_paid' => $invoice->total,
            'paid_at' => now(),
        ])->save();

        $session = ['admin' => ['email' => 'admin@mail.net']];

        // The edit form itself must render — regression guard for the
        // glued-blade-directive syntax bug on /admin/invoices/{id}/edit.
        $this->withSession($session)->withoutExceptionHandling()->get(route('admin.invoices.edit', $invoice))
            ->assertStatus(200)
            ->assertSee('Selling / year (৳)');

        $this->withSession($session)->get(route('admin.invoices'))
            ->assertStatus(200)
            ->assertSee($invoice->invoice_number);

        // Admin extends the line from 3 to 4 years and saves.
        $this->withSession($session)->post(route('admin.invoices.update', $invoice), [
            'status' => 'paid', // admin keeps "paid"; the service reopens it automatically
            'payment_method' => 'nagad',
            'vat_percent' => 0,
            'items' => [[
                'description' => 'Domain registration — invoiceable.com',
                'item_type' => 'domain-registration',
                'years' => 4,
                'quantity' => 1,
                'unit_price' => 1214,
            ]],
        ])->assertRedirect(route('admin.invoices.show', $invoice->fresh()));

        $invoice = $invoice->fresh();
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status); // reopened for the difference
        $this->assertSame(4856.0, $invoice->total);
        $this->assertSame(3642.0, (float) $invoice->amount_paid); // kept on record
        $this->assertSame(1214.0, $invoice->balanceDue());        // customer owes one extra year
        $this->assertSame(4, $invoice->items->first()->years);
        $this->assertSame(1, $invoice->items->first()->sl_no);
        $this->assertSame(1214.0, $invoice->items->first()->purchase_price);

        // The customer list must now show the INVOICE status (Unpaid, with the
        // due portion) even though the underlying order was already paid once.
        $this->withSession($this->customerSession($user))->get('/invoices')
            ->assertStatus(200)
            ->assertSee('Unpaid')
            ->assertSee($invoice->invoice_number);
    }

    public function testAdminInvoiceTableShowsDomainMarginAndPurchasePriceSnapshot()
    {
        $user = $this->customer();
        $invoice = app(InvoiceService::class)->createForOrder($this->orderWithItems($user, 2, 1214, false, 1214)->fresh());
        $session = ['admin' => ['email' => 'admin@mail.net']];

        $this->withSession($session)->post(route('admin.invoices.update', $invoice), [
            'status' => 'unpaid',
            'payment_method' => 'nagad',
            'vat_percent' => 0,
            'items' => [[
                'id' => $invoice->items->first()->id,
                'description' => 'Domain registration — invoiceable.com',
                'item_type' => 'domain-registration',
                'years' => 2,
                'quantity' => 1,
                'unit_price' => 1500,
            ]],
        ])->assertRedirect(route('admin.invoices.show', $invoice->fresh()));

        $line = $invoice->fresh()->items->first();
        $this->assertSame(1214.0, $line->purchase_price);
        $this->assertSame(1500.0, $line->unit_price);

        $this->withSession($session)->get(route('admin.invoices'))
            ->assertStatus(200)
            ->assertSee('invoiceable.com')
            ->assertSee('Test Buyer')
            ->assertSee('API purchase / year')
            ->assertSee('2')
            ->assertSee('৳ 1,214.00')
            ->assertSee('৳ 1,500.00')
            ->assertSee('৳ 572.00')
            ->assertSee('Nagad')
            ->assertSee('View')
            ->assertSee('Edit');
    }

    public function testAdminInvoiceTableAndEditorIncludeDomainTransfers()
    {
        $user = $this->customer();
        $order = $this->orderWithItems($user, 1, 1600);
        $order->items()->first()->forceFill([
            'order_type' => 'transfer',
            'purchase_price' => 1000,
        ])->save();
        $invoice = app(InvoiceService::class)->createForOrder($order->fresh());
        $session = ['admin' => ['email' => 'admin@mail.net']];

        $this->assertSame('domain-transfer', $invoice->items->first()->item_type);

        $this->withSession($session)->get(route('admin.invoices'))
            ->assertOk()
            ->assertSee('invoiceable.com')
            ->assertSee('1,000.00')
            ->assertSee('1,600.00')
            ->assertSee('600.00');

        $this->withSession($session)->get(route('admin.invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('domain-transfer"  selected', false);

        $pdfHtml = view('invoices.pdf', [
            'invoice' => $invoice->fresh()->load('items', 'user', 'source'),
            'isAdmin' => true,
        ])->render();
        $this->assertStringContainsString('invoiceable.com', $pdfHtml);
        $this->assertStringContainsString('ResellerClub', $pdfHtml);
        $this->assertStringContainsString('BDT 1,000.00', $pdfHtml);
        $this->assertStringContainsString('BDT 600.00', $pdfHtml);

        $this->withSession($session)->post(route('admin.invoices.update', $invoice), [
            'status' => 'unpaid',
            'payment_method' => 'nagad',
            'vat_percent' => 0,
            'items' => [[
                'id' => $invoice->items->first()->id,
                'description' => 'Domain transfer — invoiceable.com',
                'item_type' => 'domain-transfer',
                'years' => 1,
                'quantity' => 1,
                'unit_price' => 1600,
            ]],
        ])->assertRedirect(route('admin.invoices.show', $invoice->fresh()));

        $this->assertSame(1000.0, $invoice->fresh()->items->first()->purchase_price);
    }

    public function testResellerCostSyncRefreshesExistingRegistrationAndTransferInvoices()
    {
        config(['services.resellerclub.enabled' => true]);
        $user = $this->customer();
        $order = $this->orderWithItems($user, 1, 1500);
        $order->items()->create([
            'domain' => 'invoiceable.net',
            'tld' => 'net',
            'years' => 1,
            'price' => 1600,
            'renewal_price' => 1600,
            'purchase_price' => 1600,
            'order_type' => 'transfer',
        ]);
        $invoice = app(InvoiceService::class)->createForOrder($order->fresh());

        $resellerClub = \Mockery::mock(\App\Services\ResellerClubClient::class);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldReceive('liveRegistrationCost')->once()->with('domcno', 1)->andReturn(1000.0);
        $resellerClub->shouldReceive('liveTransferCost')->once()->with('net')->andReturn(900.0);
        $this->app->instance(\App\Services\ResellerClubClient::class, $resellerClub);

        $this->assertSame(0, Artisan::call('invoices:sync-resellerclub-costs'));

        $invoiceItems = $invoice->fresh()->items->keyBy('description');
        $this->assertSame(1000.0, $invoiceItems->get('Domain registration — invoiceable.com')->purchase_price);
        $this->assertSame(900.0, $invoiceItems->get('Domain transfer — invoiceable.net')->purchase_price);
        $this->assertSame(1000.0, $order->items()->where('domain', 'invoiceable.com')->first()->purchase_price);
        $this->assertSame(900.0, $order->items()->where('domain', 'invoiceable.net')->first()->purchase_price);
    }

    public function testTopupApprovalIssuesPaidReceiptInvoice()
    {
        $user = $this->customer();
        $topup = Topup::create([
            'user_id' => $user->id,
            'reference' => 'TU-TEST-' . uniqid(),
            'amount' => 500.00,
            'method' => 'nagad',
            'status' => 'pending',
        ]);

        $this->withSession(['admin' => ['email' => 'admin@mail.net']])
            ->post(route('admin.topups.approve', $topup))
            ->assertRedirect();

        $invoice = (new InvoiceService)->findForSource(Topup::class, $topup->id);
        $this->assertNotNull($invoice);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame(Invoice::TYPE_TOPUP, $invoice->type);
        $this->assertSame(500.0, $invoice->total);
        $this->assertSame(1, $invoice->items->count());
        $this->assertSame(0, $invoice->items->first()->years);

        // Admin re-opens the paid receipt and adds a one-off manual line
        // (years = 0): qty 2 x 250 bills 500, so the total grows to 1000 while
        // only 500 was paid — the growth rule reopens the invoice as unpaid
        // for the difference instead of inventing "already paid".
        $service = new InvoiceService;
        $service->applyEdit($invoice->fresh(), [
            ['description' => 'Wallet balance top-up (Nagad)', 'item_type' => 'topup', 'years' => 0, 'quantity' => 1, 'unit_price' => 500],
            ['description' => 'Bank transfer processing fee', 'item_type' => 'manual', 'years' => 0, 'quantity' => 2, 'unit_price' => 250],
        ], [
            'status' => 'paid', 'payment_method' => 'nagad', 'vat_percent' => 0, 'due_date' => null, 'notes' => 'Reissued receipt with processing fee.',
        ]);

        $invoice = $invoice->fresh();
        $this->assertSame(1000.0, $invoice->total); // 500 + 2 x (250 x 1)
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status); // reopened by the growth rule
        $this->assertSame(500.0, (float) $invoice->amount_paid); // received payment kept on record
        $this->assertSame(500.0, $invoice->balanceDue());
        $this->assertSame(2, $invoice->items->count());
    }

    public function testAdminCanMarkAPaidInvoiceUnpaidWithoutChangingTheTotal()
    {
        $user = $this->customer();
        $invoice = app(InvoiceService::class)->createForOrder($this->orderWithItems($user, 3, 1214, false, 1214)->fresh());
        $invoice->forceFill([
            'status' => Invoice::STATUS_PAID,
            'amount_paid' => $invoice->total,
            'paid_at' => now(),
        ])->save();

        $session = ['admin' => ['email' => 'admin@mail.net']];

        $this->withSession($session)->post(route('admin.invoices.update', $invoice), [
            'status' => 'unpaid', // explicit manual choice, total unchanged
            'payment_method' => 'nagad',
            'vat_percent' => 0,
            'items' => [[
                'id' => $invoice->items->first()->id,
                'description' => 'Domain registration — invoiceable.com',
                'item_type' => 'domain-registration',
                'years' => 3,
                'quantity' => 1,
                'unit_price' => 1214,
            ]],
        ])->assertRedirect(route('admin.invoices.show', $invoice->fresh()));

        $invoice = $invoice->fresh();
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status);
        $this->assertSame(3642.0, $invoice->total);
        // Manual "mark unpaid" reverses the payment: the full total is due again.
        $this->assertSame(0.0, (float) $invoice->amount_paid);
        $this->assertNull($invoice->paid_at);
        $this->assertSame(3642.0, $invoice->balanceDue());
    }

    public function testAdminCanTogglePaidStatusFromTheInvoicePage()
    {
        $user = $this->customer();
        $invoice = app(InvoiceService::class)->createForOrder($this->orderWithItems($user, 3, 1214, false, 1214)->fresh());
        $session = ['admin' => ['email' => 'admin@mail.net']];

        $this->withSession($session)->post(route('admin.invoices.status', $invoice), ['status' => 'paid'])
            ->assertRedirect(route('admin.invoices.show', $invoice->fresh()));

        $invoice = $invoice->fresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertSame(3642.0, (float) $invoice->amount_paid);
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame(0.0, $invoice->balanceDue());

        $this->withSession($session)->post(route('admin.invoices.status', $invoice), ['status' => 'unpaid'])
            ->assertRedirect(route('admin.invoices.show', $invoice->fresh()));

        $invoice = $invoice->fresh();
        $this->assertSame(Invoice::STATUS_UNPAID, $invoice->status);
        $this->assertSame(0.0, (float) $invoice->amount_paid);
        $this->assertNull($invoice->paid_at);
        $this->assertSame(3642.0, $invoice->balanceDue());
    }

    public function testAdminCanClearOptionalInvoiceFields()
    {
        $user = $this->customer();
        $invoice = app(InvoiceService::class)->createForOrder($this->orderWithItems($user)->fresh());
        $invoice->forceFill([
            'payment_method' => 'nagad',
            'notes' => 'Old note that should go away',
            'due_date' => now()->addDays(7),
        ])->save();

        $session = ['admin' => ['email' => 'admin@mail.net']];

        $this->withSession($session)->post(route('admin.invoices.update', $invoice), [
            'status' => 'unpaid',
            'payment_method' => '',
            'vat_percent' => 0,
            'due_date' => '',
            'notes' => '',
            'items' => [[
                'id' => $invoice->items->first()->id,
                'description' => 'Domain registration — invoiceable.com',
                'item_type' => 'domain-registration',
                'years' => 3,
                'quantity' => 1,
                'unit_price' => 1214,
            ]],
        ])->assertRedirect(route('admin.invoices.show', $invoice->fresh()));

        $invoice = $invoice->fresh();
        $this->assertNull($invoice->payment_method);
        $this->assertNull($invoice->due_date);
        $this->assertNull($invoice->notes);
    }

    public function testEditPageUsesVerticalCardsAndKeepsSnapshotsAfterAValidationError()
    {
        $user = $this->customer();
        $invoice = app(InvoiceService::class)->createForOrder($this->orderWithItems($user, 3, 1214, false, 1214)->fresh());
        $invoice->forceFill([
            'status' => Invoice::STATUS_PAID,
            'amount_paid' => $invoice->total,
            'paid_at' => now(),
        ])->save();

        $session = ['admin' => ['email' => 'admin@mail.net']];

        $this->withSession($session)->get(route('admin.invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('inv-line-head', false)
            ->assertSee('inv-settings', false)
            ->assertSee('id="paidAmount" data-value="3642"', false)
            ->assertSee('৳ 1,214.00')
            ->assertDontSee('<table class="inv-lines"', false);

        // Invalid price: the form is redisplayed from old input, but the
        // read-only purchase snapshot must still be visible.
        $this->withSession($session)->from(route('admin.invoices.edit', $invoice))
            ->post(route('admin.invoices.update', $invoice), [
                'status' => 'paid',
                'payment_method' => 'nagad',
                'vat_percent' => 0,
                'items' => [[
                    'id' => $invoice->items->first()->id,
                    'description' => 'Domain registration — invoiceable.com',
                    'item_type' => 'domain-registration',
                    'years' => 3,
                    'quantity' => 1,
                    'unit_price' => -1,
                ]],
            ])->assertSessionHasErrors('items.0.unit_price');

        $this->withSession($session)->get(route('admin.invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('value="-1"', false)
            ->assertSee('৳ 1,214.00');
    }

    public function testVoidInvoiceNoLongerOffersAnEditButton()
    {
        $user = $this->customer();
        $invoice = app(InvoiceService::class)->createForOrder($this->orderWithItems($user)->fresh());
        $invoice->forceFill(['status' => Invoice::STATUS_VOID])->save();

        $this->withSession(['admin' => ['email' => 'admin@mail.net']])
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('Edit invoice (SL, years, prices)');
    }

    /**
     * Build a two-domain invoice with a known purchase snapshot and a full
     * customer profile, so the PDF report has every requested column.
     */
    private function reportInvoice()
    {
        $user = $this->customer();
        $user->forceFill([
            'customer_uid' => 'CT-5874521',
            'phone' => '01700000000',
            'address1' => '14/2, Lake Circus',
            'address2' => 'Kalabagan',
            'city' => 'Dhaka',
            'postcode' => '1205',
            'country' => 'Bangladesh',
        ])->save();

        $order = $this->orderWithItems($user, 2, 1214);
        $order->items()->first()->update(['purchase_price' => 1000.00]);
        $order->items()->create([
            'domain' => 'seconddomain.com',
            'tld' => 'com',
            'years' => 1,
            'price' => 500.00,
            'renewal_price' => 500.00,
            'purchase_price' => 300.00,
        ]);
        $order->update(['total' => 2928.00]);

        return app(InvoiceService::class)->createForOrder($order->fresh());
    }

    public function testAdminPdfShowsTheFullCustomerInformationReport()
    {
        $invoice = $this->reportInvoice();

        $html = view('invoices.pdf', [
            'invoice' => $invoice->fresh()->load('items', 'user', 'source'),
            'isAdmin' => true,
        ])->render();

        $this->assertStringContainsString('Cloud Technology Customer Information', $html);
        $this->assertStringContainsString('Product Purchase Amount BDT', $html);
        $this->assertStringContainsString('Product Sales Amount', $html);
        $this->assertStringContainsString('Profit Amount', $html);
        $this->assertStringContainsString('invoiceable.com', $html);
        $this->assertStringContainsString('seconddomain.com', $html);
        $this->assertStringContainsString('BDT 2,300.00', $html); // purchase total
        $this->assertStringContainsString('BDT 2,928.00', $html); // sales total
        $this->assertStringContainsString('BDT 628.00', $html);  // profit total
        $this->assertStringContainsString('Test Buyer', $html);
        $this->assertStringContainsString('CT-5874521', $html);
        $this->assertStringContainsString('14/2, Lake Circus', $html);
        $this->assertStringContainsString('Kalabagan', $html);
        $this->assertStringContainsString('buyer@example.test', $html);
        $this->assertStringContainsString('01700000000', $html);

        // One row per domain line + 11 admin columns.
        $this->assertSame(2, substr_count($html, '<td class="email">'));
        $this->assertSame(11, substr_count($html, '<th '));
    }

    public function testCustomerPdfHidesPurchaseAndProfit()
    {
        $invoice = $this->reportInvoice();

        $html = view('invoices.pdf', [
            'invoice' => $invoice->fresh()->load('items', 'user', 'source'),
            'isAdmin' => false,
        ])->render();

        $this->assertStringContainsString('Cloud Technology', $html);
        $this->assertStringContainsString('ResellerClub', $html);
        $this->assertStringContainsString('invoiceable.com', $html);
        $this->assertStringContainsString('seconddomain.com', $html);
        $this->assertStringContainsString('BDT 2,928.00', $html);
        $this->assertStringContainsString('Test Buyer', $html);
        $this->assertStringContainsString('buyer@example.test', $html);

        // Internal margin data must never reach the customer document.
        $this->assertStringNotContainsString('Product Purchase Amount', $html);
        $this->assertStringNotContainsString('Profit Amount', $html);
        $this->assertStringNotContainsString('BDT 2,300.00', $html);
        $this->assertStringNotContainsString('BDT 628.00', $html);
        $this->assertStringNotContainsString('Do not send to the customer', $html);

        // One row per domain line + 9 customer-safe columns.
        $this->assertSame(2, substr_count($html, '<td class="email">'));
        $this->assertSame(9, substr_count($html, '<th '));
    }

    public function testAdminCanDownloadTheCustomerInformationPdf()
    {
        $invoice = $this->reportInvoice();

        $response = $this->withSession(['admin' => ['email' => 'admin@mail.net']])
            ->get(route('admin.invoices.pdf', $invoice));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->withSession(['admin' => ['email' => 'admin@mail.net']])
            ->get(route('admin.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Download customer information PDF')
            ->assertSee(route('admin.invoices.pdf', $invoice), false);
    }

    public function testTopupInvoicePdfStillRendersOneSafeRow()
    {
        $user = $this->customer();
        $topup = Topup::create([
            'user_id' => $user->id,
            'reference' => 'TU-PDF-' . uniqid(),
            'amount' => 500.00,
            'method' => 'nagad',
            'status' => 'pending',
        ]);

        $this->withSession(['admin' => ['email' => 'admin@mail.net']])
            ->post(route('admin.topups.approve', $topup))
            ->assertRedirect();

        $invoice = (new InvoiceService)->findForSource(Topup::class, $topup->id);

        $html = view('invoices.pdf', [
            'invoice' => $invoice->fresh()->load('items', 'user', 'source'),
            'isAdmin' => true,
        ])->render();

        $this->assertStringContainsString('Wallet balance top-up', $html);
        $this->assertStringContainsString('Nagad', $html); // non-domain provider fallback
        $this->assertStringContainsString('BDT 500.00', $html);
        $this->assertStringContainsString('no saved purchase price', $html); // unknown-cost note
    }
}
