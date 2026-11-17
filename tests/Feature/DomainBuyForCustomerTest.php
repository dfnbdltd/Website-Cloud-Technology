<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\DomainOrderItem;
use App\Models\Invoice;
use App\Support\CustomerLookup;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Domain checkout: "Who are you buying for?" — myself or an existing customer.
 *
 * Uses a configured test domain so no ResellerClub call is made: the order,
 * invoice and ownership rules are what is under test here.
 */
class DomainBuyForCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DomainOrder::resetPlacedByCache();
        Mail::fake();

        config(['services.test_domains' => [
            'example.com' => ['price' => 1200, 'renewal' => 1200],
        ]]);
    }

    private function user($name, $verified = true, $uid = null, $balance = 0)
    {
        $user = new User([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '-' . uniqid() . '@example.test',
            'password' => Hash::make('SecretP@ss1'),
        ]);
        $user->forceFill([
            'email_verified_at' => $verified ? now() : null,
            'customer_uid' => $uid,
            'balance' => $balance,
            'phone' => '01700000000',
        ])->save();

        return $user;
    }

    private function session(User $user, array $extra = [])
    {
        return array_merge(['customer' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => (string) $user->phone,
            'balance' => (float) $user->balance,
        ]], $extra);
    }

    private function cart()
    {
        return ['domain_cart' => ['example.com' => [
            'domain' => 'example.com',
            'order_type' => 'registration',
            'price' => 1200,
            'purchase_price' => null,
            'first_year_price' => 1200,
            'renewal_price' => 1200,
            'years' => 1,
        ]]];
    }

    // ------------------------------------------------------------ the page

    public function test_signed_in_checkout_asks_who_the_domain_is_for()
    {
        $buyer = $this->user('Buyer One');

        $this->withSession($this->session($buyer, $this->cart()))
            ->get(route('domains.checkout'))
            ->assertOk()
            ->assertSee('Who are you buying for?')
            ->assertSee($buyer->email)
            ->assertSee('Email or ID of existing customer')
            ->assertSee('Confirm');
    }

    public function test_guest_checkout_still_asks_to_sign_in_and_has_no_customer_picker()
    {
        $this->withSession($this->cart())
            ->get(route('domains.checkout'))
            ->assertOk()
            ->assertSee('Log in to confirm')
            ->assertDontSee('Who are you buying for?');
    }

    // ------------------------------------------------------ customer lookup

    public function test_lookup_finds_verified_customers_by_email_or_id_only()
    {
        $buyer = $this->user('Buyer One');
        $customer = $this->user('Existing Customer', true, 'CT-000042');
        $unverified = $this->user('Not Verified', false, 'CT-000043');
        $session = $this->session($buyer);

        $this->withSession($session)->postJson(route('domains.customer.lookup'), ['identifier' => $customer->email])
            ->assertOk()->assertJson(['verified' => true]);
        $this->withSession($session)->postJson(route('domains.customer.lookup'), ['identifier' => 'CT-000042'])
            ->assertOk()->assertJson(['verified' => true]);

        $this->withSession($session)->postJson(route('domains.customer.lookup'), ['identifier' => 'nobody@example.test'])
            ->assertStatus(404)->assertJson(['message' => CustomerLookup::NOT_FOUND]);
        $this->withSession($session)->postJson(route('domains.customer.lookup'), ['identifier' => $unverified->email])
            ->assertStatus(404);
        $this->withSession($session)->postJson(route('domains.customer.lookup'), ['identifier' => 'CT-999999'])
            ->assertStatus(404);
    }

    public function test_lookup_needs_a_signed_in_customer()
    {
        $this->postJson(route('domains.customer.lookup'), ['identifier' => 'a@b.test'])->assertStatus(401);
    }

    // --------------------------------------------------------- placing it

    public function test_buying_for_a_customer_makes_them_the_owner_and_records_the_buyer()
    {
        $buyer = $this->user('Buyer One');
        $customer = $this->user('Existing Customer', true, 'CT-000042');

        $this->withSession($this->session($buyer, $this->cart()))
            ->post(route('domains.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => 'CT-000042',
                'payment_method' => 'bkash',
            ])->assertOk();

        $order = DomainOrder::firstOrFail();
        $this->assertSame($customer->id, (int) $order->user_id);
        $this->assertSame($buyer->id, (int) $order->placed_by_user_id);
        $this->assertSame($customer->email, $order->customer_email);
        $this->assertSame($customer->name, $order->customer_name);
        $this->assertSame(1200.0, (float) $order->total);

        // The invoice (and so My Store → Invoices) belongs to the customer.
        $invoice = Invoice::where('source_type', DomainOrder::class)->where('source_id', $order->id)->firstOrFail();
        $this->assertSame($customer->id, (int) $invoice->user_id);
    }

    public function test_buying_for_myself_keeps_the_buyer_as_owner_and_placer()
    {
        $buyer = $this->user('Buyer One');

        $this->withSession($this->session($buyer, $this->cart()))
            ->post(route('domains.place-order'), [
                'purchase_for' => 'myself',
                'payment_method' => 'bkash',
            ])->assertOk();

        $order = DomainOrder::firstOrFail();
        $this->assertSame($buyer->id, (int) $order->user_id);
        $this->assertSame($buyer->id, (int) $order->placed_by_user_id);
        $this->assertSame($buyer->email, $order->customer_email);
    }

    public function test_an_unknown_customer_is_refused_and_nothing_is_saved()
    {
        $buyer = $this->user('Buyer One');

        $this->withSession($this->session($buyer, $this->cart()))
            ->from(route('domains.checkout'))
            ->post(route('domains.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => 'ghost@example.test',
                'payment_method' => 'bkash',
            ])
            ->assertRedirect(route('domains.checkout'))
            ->assertSessionHasErrors(['customer_identifier' => CustomerLookup::NOT_FOUND]);

        $this->assertSame(0, DomainOrder::count());
        $this->assertSame(0, Invoice::count());
    }

    public function test_an_unverified_customer_is_refused()
    {
        $buyer = $this->user('Buyer One');
        $unverified = $this->user('Not Verified', false, 'CT-000043');

        $this->withSession($this->session($buyer, $this->cart()))
            ->from(route('domains.checkout'))
            ->post(route('domains.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => $unverified->email,
                'payment_method' => 'bkash',
            ])->assertSessionHasErrors('customer_identifier');

        $this->assertSame(0, DomainOrder::count());
    }

    public function test_the_customer_field_is_required_when_buying_for_a_customer()
    {
        $buyer = $this->user('Buyer One');

        $this->withSession($this->session($buyer, $this->cart()))
            ->from(route('domains.checkout'))
            ->post(route('domains.place-order'), [
                'purchase_for' => 'customer',
                'payment_method' => 'bkash',
            ])->assertSessionHasErrors('customer_identifier');

        $this->assertSame(0, DomainOrder::count());
    }

    public function test_a_guest_cannot_buy_for_a_customer()
    {
        $this->user('Existing Customer', true, 'CT-000042');

        $this->withSession($this->cart())
            ->post(route('domains.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => 'CT-000042',
                'payment_method' => 'bkash',
            ])->assertRedirect(route('login', ['return_to' => route('domains.checkout', [], false)]));

        $this->assertSame(0, DomainOrder::count());
    }

    public function test_wallet_payment_for_a_customer_charges_the_buyer_not_the_customer()
    {
        $buyer = $this->user('Buyer One', true, null, 5000);
        $customer = $this->user('Existing Customer', true, 'CT-000042', 100);

        $this->withSession($this->session($buyer, $this->cart()))
            ->post(route('domains.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => 'CT-000042',
                'payment_method' => 'wallet',
            ])->assertOk();

        $this->assertEquals(3800.0, (float) $buyer->fresh()->balance);
        $this->assertEquals(100.0, (float) $customer->fresh()->balance);

        $order = DomainOrder::firstOrFail();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame($customer->id, (int) $order->user_id);
    }

    // ------------------------------------------------- who may open the order

    private function orderFor(User $owner, User $placedBy)
    {
        $order = DomainOrder::create([
            'reference' => 'CT-TEST-' . strtoupper(substr(uniqid(), -6)),
            'user_id' => $owner->id,
            'placed_by_user_id' => $placedBy->id,
            'total' => 1200,
            'status' => 'payment_pending',
            'payment_method' => 'bkash',
            'payment_status' => 'pending',
            'provider' => 'bkash',
            'customer_name' => $owner->name,
            'customer_phone' => '01700000000',
            'customer_email' => $owner->email,
        ]);
        DomainOrderItem::create([
            'domain_order_id' => $order->id,
            'domain' => 'example.com',
            'tld' => 'com',
            'years' => 1,
            'price' => 1200,
            'renewal_price' => 1200,
            'purchase_price' => 1200,
            'order_type' => 'registration',
        ]);

        return $order;
    }

    public function test_the_buyer_and_the_owner_can_open_the_confirmation_but_a_stranger_cannot()
    {
        $buyer = $this->user('Buyer One');
        $customer = $this->user('Existing Customer', true, 'CT-000042');
        $stranger = $this->user('Stranger Three');
        $order = $this->orderFor($customer, $buyer);

        $this->withSession($this->session($buyer))
            ->get(route('domains.confirmation', $order->reference))->assertOk();
        $this->withSession($this->session($customer))
            ->get(route('domains.confirmation', $order->reference))->assertOk();
        $this->withSession($this->session($stranger))
            ->get(route('domains.confirmation', $order->reference))
            ->assertRedirect(route('domains.index'));
    }
}
