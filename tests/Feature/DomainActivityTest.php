<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\Payment;
use App\Services\InvoiceService;
use App\Services\ResellerClubClient;
use App\Support\DomainActivity;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Domain management shows ResellerClub's own activity for the domain the
 * customer opened: the latest three on the right, everything under View All.
 */
class DomainActivityTest extends TestCase
{
    use RefreshDatabase;

    // 2026-11-05 06:12:45 UTC, 2026-11-10 11:06:44 UTC, 2026-11-10 11:07:28 UTC
    private const T_OLDEST = 1793859165;
    private const T_MIDDLE = 1794308804;
    private const T_NEWEST = 1794308848;

    public function testManagePageShowsTheLatestThreeActivitiesNewestFirstWithViewAll()
    {
        $user = $this->user();
        $item = $this->registeredOrder($user, 'ACT-ORDER-1', ['activity-one.test'])[0];

        $client = $this->resellerClub();
        $client->shouldReceive('domainOrderId')->once()->with('activity-one.test')->andReturn('501');
        $client->shouldReceive('nameservers')->once()->with('501')->andReturn(['ns1.a.test', 'ns2.a.test']);
        $client->shouldReceive('domainActions')->once()->with('501')->andReturn([
            $this->action('4', 'Fourth newest change', self::T_NEWEST),
            $this->action('3', 'Third change', self::T_MIDDLE),
            $this->action('2', 'Second change', self::T_MIDDLE - 60),
            $this->action('1', 'Oldest change', self::T_OLDEST),
        ]);

        $response = $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.details', $item));

        $response->assertOk()
            ->assertSee('LATEST ACTIVITY')
            ->assertSeeInOrder(['Fourth newest change', 'Third change', 'Second change'])
            ->assertDontSee('Oldest change')
            ->assertSee('10th Nov, 2026')
            ->assertSee('store-latest-viewall', false)
            ->assertSee(route('mystore.domains.activity', $item->id), false);

        // The ID is remembered on the item so the next view skips the lookup.
        $this->assertSame('501', $item->fresh()->reseller_order_id);
    }

    public function testManagePageSaysNoActivityWhenTheDomainHasNone()
    {
        $user = $this->user();
        $item = $this->registeredOrder($user, 'ACT-ORDER-2', ['quiet-domain.test'])[0];

        $client = $this->resellerClub();
        $client->shouldReceive('domainOrderId')->andReturn('502');
        $client->shouldReceive('nameservers')->andReturn([]);
        $client->shouldReceive('domainActions')->once()->with('502')->andReturn([]);

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.details', $item))
            ->assertOk()
            ->assertSee('LATEST ACTIVITY')
            ->assertSee('No activity')
            ->assertDontSee('store-latest-viewall', false)
            ->assertDontSee('temporarily unavailable');
    }

    public function testManagePageDoesNotClaimNoActivityWhenResellerClubCannotBeRead()
    {
        $user = $this->user();
        $item = $this->registeredOrder($user, 'ACT-ORDER-3', ['offline-domain.test'])[0];

        $client = $this->resellerClub();
        $client->shouldReceive('domainOrderId')->andReturn('503');
        $client->shouldReceive('nameservers')->andReturn([]);
        $client->shouldReceive('domainActions')->once()->with('503')->andReturn(null);

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.details', $item))
            ->assertOk()
            ->assertSee('Activity is temporarily unavailable')
            ->assertDontSee('No activity');
    }

    public function testEveryDomainOfAMultiDomainOrderShowsItsOwnActivity()
    {
        $user = $this->user();
        list($first, $second) = $this->registeredOrder($user, 'ACT-ORDER-4', ['first-domain.test', 'second-domain.test']);

        $client = $this->resellerClub();
        $client->shouldReceive('domainOrderId')->once()->with('first-domain.test')->andReturn('901');
        $client->shouldReceive('domainOrderId')->once()->with('second-domain.test')->andReturn('902');
        $client->shouldReceive('nameservers')->andReturn([]);
        $client->shouldReceive('domainActions')->once()->with('901')
            ->andReturn([$this->action('11', 'Action on the FIRST domain', self::T_MIDDLE)]);
        $client->shouldReceive('domainActions')->once()->with('902')
            ->andReturn([$this->action('22', 'Action on the SECOND domain', self::T_NEWEST)]);

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.details', $second))
            ->assertOk()
            ->assertSee('Action on the SECOND domain')
            ->assertDontSee('Action on the FIRST domain');

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.details', $first))
            ->assertOk()
            ->assertSee('Action on the FIRST domain')
            ->assertDontSee('Action on the SECOND domain');

        $this->assertSame('901', $first->fresh()->reseller_order_id);
        $this->assertSame('902', $second->fresh()->reseller_order_id);
    }

    public function testAStoredOrderIdIsUsedWithoutLookingTheDomainUpAgain()
    {
        $user = $this->user();
        $item = $this->registeredOrder($user, 'ACT-ORDER-5', ['stored-id.test'])[0];
        $item->update(['reseller_order_id' => '777']);

        $client = $this->resellerClub();
        $client->shouldReceive('domainOrderId')->never();
        $client->shouldReceive('nameservers')->once()->with('777')->andReturn([]);
        $client->shouldReceive('domainActions')->once()->with('777')
            ->andReturn([$this->action('5', 'Stored id action', self::T_NEWEST)]);

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.details', $item))
            ->assertOk()
            ->assertSee('Stored id action');
    }

    public function testViewAllListsEveryActionGroupedByDateAndKeepsTheOrderHistory()
    {
        $user = $this->user();
        $item = $this->registeredOrder($user, 'ACT-ORDER-6', ['full-list.test'])[0];

        $client = $this->resellerClub();
        $client->shouldReceive('domainOrderId')->andReturn('506');
        $client->shouldReceive('domainActions')->once()->with('506')->andReturn([
            $this->action('4', 'Contact change two', self::T_NEWEST),
            $this->action('3', 'Contact change one', self::T_MIDDLE),
            $this->action('2', 'Consent change two', self::T_OLDEST + 2),
            $this->action('1', 'Consent change one', self::T_OLDEST),
        ]);

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.activity', $item))
            ->assertOk()
            ->assertSeeInOrder(['10th Nov, 2026', 'Contact change two', 'Contact change one', '5th Nov, 2026', 'Consent change two', 'Consent change one'])
            ->assertSee('COMPLETED')
            ->assertSee('Started:')
            ->assertSee('DOMAIN REGISTRATION')
            // The existing order / payment / invoice record is kept below the timeline.
            ->assertSee('Order &amp; payment history', false)
            ->assertSee('Domain order placed');
    }

    public function testViewAllSaysNoActivityAndUnavailableHonestly()
    {
        $user = $this->user();
        $item = $this->registeredOrder($user, 'ACT-ORDER-7', ['empty-list.test'])[0];

        $client = $this->resellerClub();
        $client->shouldReceive('domainOrderId')->andReturn('507');
        $client->shouldReceive('domainActions')->with('507')->andReturn([], null);

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.activity', $item))
            ->assertOk()
            ->assertSee('No activity');

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.activity', $item))
            ->assertOk()
            ->assertSee('Activity is temporarily unavailable')
            ->assertDontSee('No activity');
    }

    public function testAnotherCustomerCannotOpenSomeoneElsesDomainActivity()
    {
        $owner = $this->user();
        $item = $this->registeredOrder($owner, 'ACT-ORDER-8', ['private-domain.test'])[0];

        $client = $this->resellerClub();
        $client->shouldReceive('domainActions')->never();

        $this->withSession($this->customerSession($this->user()))
            ->get(route('mystore.domains.activity', $item))
            ->assertNotFound();
    }

    public function testChangingNameserversClearsTheCachedActivityList()
    {
        $user = $this->user();
        $item = $this->registeredOrder($user, 'ACT-ORDER-9', ['refresh-domain.test'])[0];

        $client = $this->resellerClub();
        $client->shouldReceive('domainOrderId')->with('refresh-domain.test')->andReturn('509');
        $client->shouldReceive('modifyNameservers')->once()
            ->with('509', ['ns1.example.com', 'ns2.example.com'])
            ->andReturn(['status' => 'SUCCESS']);

        $key = ResellerClubClient::domainActionsCacheKey('509');
        Cache::put($key, [$this->action('1', 'Stale list', self::T_OLDEST)], 60);

        $this->withSession($this->customerSession($user))
            ->post(route('mystore.domains.nameservers', $item), [
                'nameservers' => "ns1.example.com\nns2.example.com",
            ])
            ->assertRedirect(route('mystore.domains.details', $item));

        $this->assertFalse(Cache::has($key));
    }

    public function testOpeningManageAlwaysReadsFreshFromResellerClubNotTheShortCopy()
    {
        $user = $this->user();
        $item = $this->registeredOrder($user, 'ACT-ORDER-10', ['always-fresh.test'])[0];
        $item->update(['reseller_order_id' => '610']);

        $client = $this->resellerClub();
        $client->shouldReceive('nameservers')->andReturn([]);
        $client->shouldReceive('domainActions')->twice()->with('610')
            ->andReturn([$this->action('9', 'Fresh from ResellerClub', self::T_NEWEST)]);

        // A copy from a few seconds ago, which the page must not rely on.
        $key = ResellerClubClient::domainActionsCacheKey('610');
        Cache::put($key, [$this->action('1', 'Old short copy', self::T_OLDEST)], 60);

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.details', $item))
            ->assertOk()
            ->assertSee('Fresh from ResellerClub')
            ->assertDontSee('Old short copy');

        $this->assertFalse(Cache::has($key));

        // The full activity page reads fresh too.
        Cache::put($key, [$this->action('1', 'Old short copy', self::T_OLDEST)], 60);

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.activity', $item))
            ->assertOk()
            ->assertSee('Fresh from ResellerClub')
            ->assertDontSee('Old short copy');
    }

    // ── helpers ──────────────────────────────────────────────────────────

    /** @return \Mockery\MockInterface */
    private function resellerClub()
    {
        $client = \Mockery::mock(ResellerClubClient::class);
        $this->app->instance(ResellerClubClient::class, $client);

        return $client;
    }

    /** A row exactly as ResellerClubClient::domainActions() returns it. */
    private function action($id, $title, $timestamp)
    {
        return DomainActivity::normalize([
            'eaqid' => $id,
            'entityid' => '1',
            'actiontype' => 'ModContact',
            'actiontypedesc' => $title,
            'actionstatus' => 'Success',
            'actionstatusdesc' => 'Done',
            'actionadded' => (string) $timestamp,
            'actionstarted' => (string) $timestamp,
            'actioncompleted' => (string) $timestamp,
        ]);
    }

    private function user()
    {
        return User::create([
            'name' => 'Activity Tester',
            'email' => 'activity-' . uniqid() . '@example.test',
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
            'initials' => 'AT',
            'email' => $user->email,
            'phone' => '01700000000',
            'balance' => 0,
            'logo' => null,
            'plan' => 'BDIX Premium',
        ]];
    }

    /**
     * One paid, active order holding a registered item per domain.
     *
     * @return \App\Models\DomainOrderItem[]
     */
    private function registeredOrder(User $user, $reference, array $domains)
    {
        $order = DomainOrder::create([
            'reference' => $reference,
            'user_id' => $user->id,
            'total' => 1200 * count($domains),
            'status' => 'active',
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'provider' => 'bkash',
            'customer_name' => $user->name,
            'customer_phone' => '01700000000',
            'customer_email' => $user->email,
        ]);

        $items = [];
        foreach ($domains as $domain) {
            $items[] = $order->items()->create([
                'domain' => $domain,
                'tld' => 'test',
                'years' => 1,
                'price' => 1200,
                'renewal_price' => 1300,
                'purchase_price' => 1000,
                'registration_status' => 'registered',
            ]);
        }

        Payment::create([
            'domain_order_id' => $order->id,
            'reference' => $reference . '-PAY',
            'provider' => 'bkash',
            'amount' => 1200 * count($domains),
            'status' => 'paid',
        ]);

        app(InvoiceService::class)->createForOrder($order->fresh());

        return $items;
    }
}
