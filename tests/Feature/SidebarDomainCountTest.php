<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Support\StoreInventory;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The sidebar shows how many domains the customer purchased beside My Store > Domain.
 */
class SidebarDomainCountTest extends TestCase
{
    use RefreshDatabase;

    /** Any customer page draws the sidebar; payments is the cheapest one. */
    private const PAGE = '/mystore/payments';

    public function testNoBadgeWhenTheCustomerHasNoDomains()
    {
        $user = $this->user();

        $this->withSession($this->customerSession($user))
            ->get(self::PAGE)
            ->assertOk()
            ->assertSee('data-sidebar-label="Domain"', false)
            ->assertDontSee('nav-subcount', false);
    }

    public function testBadgeCountsRegistrationsAndSucceededTransfersOnly()
    {
        $user = $this->user();
        $this->order($user, 'SB-REG', 'paid', 'active', [
            ['one.test', 'registration', 'registered'],
            ['two.test', 'registration', 'registered'],
        ]);
        $this->order($user, 'SB-TRANSFER-OK', 'paid', 'transfer_pending', [
            ['moved.test', 'transfer', 'transferred'],
        ]);
        $this->order($user, 'SB-TRANSFER-WAIT', 'paid', 'transfer_pending', [
            ['waiting.test', 'transfer', 'transfer_submitted'],
        ]);
        // Never paid: not purchased.
        $this->order($user, 'SB-UNPAID', 'pending', 'pending', [
            ['unpaid.test', 'registration', 'pending'],
        ]);
        // Somebody else's domain.
        $this->order($this->user(), 'SB-OTHER', 'paid', 'active', [
            ['other.test', 'registration', 'registered'],
        ]);

        $this->assertSame(3, StoreInventory::domainCountForUser($user->id));
        // Always the same number the All Items page uses.
        $this->assertSame((new StoreInventory($user->id))->domainCount(), StoreInventory::domainCountForUser($user->id));

        $this->withSession($this->customerSession($user))
            ->get(self::PAGE)
            ->assertOk()
            ->assertSee('class="nav-count nav-subcount"', false)
            ->assertSee('title="3 domains purchased">3</i>', false);
    }

    public function testSingularTitleAndNinetyNinePlusCap()
    {
        $one = $this->user();
        $this->order($one, 'SB-ONE', 'paid', 'active', [['solo.test', 'registration', 'registered']]);
        $this->withSession($this->customerSession($one))
            ->get(self::PAGE)
            ->assertSee('title="1 domain purchased">1</i>', false);

        $many = $this->user();
        $rows = [];
        for ($i = 1; $i <= 100; $i++) {
            $rows[] = ['bulk' . $i . '.test', 'registration', 'registered'];
        }
        $this->order($many, 'SB-MANY', 'paid', 'active', $rows);
        $this->withSession($this->customerSession($many))
            ->get(self::PAGE)
            ->assertSee('title="100 domains purchased">99+</i>', false);
    }

    public function testNoUserMeansNoCountAndNoError()
    {
        $this->assertSame(0, StoreInventory::domainCountForUser(null));
        $this->assertSame(0, StoreInventory::domainCountForUser(0));
        $this->assertSame(0, StoreInventory::domainCountForUser('abc'));
    }

    // ── helpers ──────────────────────────────────────────────────────────

    /** @param  array  $items  [domain, order_type, registration_status] */
    private function order(User $user, $reference, $paymentStatus, $status, array $items)
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

        foreach ($items as $item) {
            $order->items()->create([
                'domain' => $item[0],
                'tld' => 'test',
                'years' => 1,
                'price' => 1200,
                'renewal_price' => 1300,
                'purchase_price' => 1000,
                'order_type' => $item[1],
                'registration_status' => $item[2],
            ]);
        }

        return $order;
    }

    private function user()
    {
        return User::create([
            'name' => 'Badge Tester',
            'email' => 'badge-' . uniqid() . '@example.test',
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
            'initials' => 'BT',
            'email' => $user->email,
            'phone' => '01700000000',
            'balance' => 0,
            'logo' => null,
            'plan' => 'BDIX Premium',
        ]];
    }
}
