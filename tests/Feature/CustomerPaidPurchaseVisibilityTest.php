<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPaidPurchaseVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function customerSession(User $user)
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

    private function user()
    {
        return User::create([
            'name' => 'Paid Purchase Tester',
            'email' => 'paid-' . uniqid() . '@example.test',
            'password' => Hash::make('SecretP@ss1'),
            'email_verified_at' => now(),
        ]);
    }

    private function order(User $user, $status, $paymentStatus, $reference)
    {
        $order = DomainOrder::create([
            'reference' => $reference,
            'user_id' => $user->id,
            'total' => 1200,
            'status' => $status,
            'payment_method' => 'bkash',
            'payment_status' => $paymentStatus,
            'provider' => 'bkash',
            'customer_name' => $user->name,
            'customer_phone' => '01700000000',
            'customer_email' => $user->email,
        ]);
        $order->items()->create([
            'domain' => $reference . '.test',
            'tld' => 'test',
            'years' => 1,
            'price' => 1200,
            'renewal_price' => 1200,
            'purchase_price' => 1200,
        ]);
        Payment::create([
            'domain_order_id' => $order->id,
            'reference' => $reference . '-PAY',
            'provider' => 'bkash',
            'amount' => 1200,
            'status' => $paymentStatus === 'paid' ? 'paid' : 'pending',
        ]);

        app(InvoiceService::class)->createForOrder($order->fresh());

        return $order->fresh();
    }

    public function test_customer_surfaces_show_paid_orders_only()
    {
        $user = $this->user();
        $paid = $this->order($user, 'paid', 'paid', 'PAID-ORDER');
        $this->order($user, 'payment_pending', 'pending', 'PENDING-ORDER');
        $this->order($user, 'cancelled', 'cancelled', 'CANCELLED-ORDER');

        $session = $this->customerSession($user);

        $this->withSession($session)->get(route('dashboard'))
            ->assertOk()
            ->assertSee($paid->reference)
            ->assertDontSee('PENDING-ORDER')
            ->assertDontSee('CANCELLED-ORDER')
            ->assertSee('Paid');

        $this->withSession($session)->get(route('mystore'))
            ->assertOk()
            ->assertSee($paid->reference)
            ->assertDontSee('PENDING-ORDER')
            ->assertDontSee('CANCELLED-ORDER');

        $this->withSession($session)->get(route('invoices'))
            ->assertOk()
            ->assertSee($paid->reference)
            ->assertDontSee('PENDING-ORDER')
            ->assertDontSee('CANCELLED-ORDER');
    }

    public function test_bkash_cancel_removes_unpaid_invoice_and_attempt()
    {
        $user = $this->user();
        $order = $this->order($user, 'payment_pending', 'pending', 'CANCEL-ME');
        $invoice = Invoice::where('source_id', $order->id)->where('source_type', DomainOrder::class)->firstOrFail();

        $response = $this->withSession([
            'last_domain_order' => [
                'reference' => $order->reference,
                'database_id' => $order->id,
                'payment' => ['method' => 'bkash'],
            ],
        ])->post(route('bkash.cancel'));

        $response->assertOk()->assertJson(['status' => 'cancelled']);
        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
        ]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('payments', ['domain_order_id' => $order->id]);
    }
}
