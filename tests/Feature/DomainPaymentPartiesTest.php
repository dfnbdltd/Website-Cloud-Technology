<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\Topup;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The payment pages (bKash page, and the order page Nagad / bank orders land on)
 * must show WHO the domain is for and WHICH domain is being bought:
 *   - Myself   -> the buyer's own name / email / phone
 *   - Customer -> that customer's name / email / phone
 *
 * Uses a configured test domain so no ResellerClub call is made.
 */
class DomainPaymentPartiesTest extends TestCase
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

    private function user($name, $phone, $uid = null)
    {
        $user = new User([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '-' . uniqid() . '@example.test',
            'password' => Hash::make('SecretP@ss1'),
        ]);
        $user->forceFill([
            'email_verified_at' => now(),
            'customer_uid' => $uid,
            'balance' => 0,
            'phone' => $phone,
        ])->save();

        return $user;
    }

    private function customerSession(User $user, array $extra = [])
    {
        return array_merge(['customer' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => (string) $user->phone,
            'balance' => 0.0,
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

    public function test_bkash_page_shows_my_own_details_and_the_domain_when_buying_for_myself()
    {
        $buyer = $this->user('Buyer One', '01711111111');

        $this->withSession($this->customerSession($buyer, $this->cart()))
            ->post(route('domains.place-order'), ['purchase_for' => 'myself', 'payment_method' => 'bkash'])
            ->assertOk();

        $this->get(route('bkash.pay'))
            ->assertOk()
            ->assertViewIs('domains.bkash-payment')
            ->assertSee('example.com')
            ->assertSee('Registration')
            ->assertSee('Buyer One')
            ->assertSee($buyer->email)
            ->assertSee('01711111111')
            ->assertSee('<b class="ordp-tag is-self">Myself</b>', false);
    }

    public function test_bkash_page_shows_the_customers_details_when_buying_for_a_customer()
    {
        $buyer = $this->user('Buyer One', '01711111111');
        $customer = $this->user('Existing Customer', '01822222222', 'CT-000042');

        $this->withSession($this->customerSession($buyer, $this->cart()))
            ->post(route('domains.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => 'CT-000042',
                'payment_method' => 'bkash',
            ])->assertOk();

        $this->get(route('bkash.pay'))
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('Existing Customer')
            ->assertSee($customer->email)
            ->assertSee('01822222222')
            ->assertSee('<b class="ordp-tag">Customer</b>', false)
            ->assertDontSee('<b class="ordp-tag is-self">Myself</b>', false)
            // The buyer's own phone is not the person the domain is for.
            ->assertDontSee('01711111111');
    }

    public function test_nagad_order_page_shows_who_it_is_for_and_the_domain()
    {
        $buyer = $this->user('Buyer One', '01711111111');
        $customer = $this->user('Existing Customer', '01822222222', 'CT-000042');

        $this->withSession($this->customerSession($buyer, $this->cart()))
            ->post(route('domains.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => $customer->email,
                'payment_method' => 'nagad',
            ])
            ->assertOk()
            ->assertViewIs('domains.confirmation')
            ->assertSee('example.com')
            ->assertSee('Existing Customer')
            ->assertSee('01822222222')
            ->assertSee('<b class="ordp-tag">Customer</b>', false);
    }

    public function test_order_page_rebuilt_from_the_database_still_shows_the_customer_and_domain()
    {
        $buyer = $this->user('Buyer One', '01711111111');
        $customer = $this->user('Existing Customer', '01822222222', 'CT-000042');

        $this->withSession($this->customerSession($buyer, $this->cart()))
            ->post(route('domains.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => 'CT-000042',
                'payment_method' => 'nagad',
            ])->assertOk();
        $order = DomainOrder::firstOrFail();

        // A brand-new session: nothing is left of the order but the database row.
        $this->flushSession();
        $this->withSession($this->customerSession($buyer))
            ->get(route('domains.confirmation', $order->reference))
            ->assertOk()
            ->assertSee('example.com')
            ->assertSee('Existing Customer')
            ->assertSee($customer->email)
            ->assertSee('01822222222')
            ->assertSee('<b class="ordp-tag">Customer</b>', false);
    }

    public function test_wallet_top_up_page_is_unchanged_and_has_no_domain_card()
    {
        $user = $this->user('Wallet User', '01733333333');
        $topup = Topup::create([
            'user_id' => $user->id,
            'reference' => 'TU-TEST-0001',
            'amount' => 500,
            'method' => 'bkash',
            'status' => 'pending',
        ]);

        $this->withSession($this->customerSession($user, ['wallet_topup_id' => $topup->id]))
            ->get(route('wallet.bkash.pay'))
            ->assertOk()
            ->assertSee('TU-TEST-0001')
            ->assertDontSee('class="ordp"', false);
    }
}
