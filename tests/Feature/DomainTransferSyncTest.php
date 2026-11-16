<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\DomainOrderItem;
use App\Services\DomainTransferSyncService;
use App\Services\InvoiceService;
use App\Services\ResellerClubClient;
use App\Support\DomainActivity;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ResellerClub is the source of truth for a transfer-in: our orders follow it,
 * and a finished transfer is managed exactly like a registered domain.
 */
class DomainTransferSyncTest extends TestCase
{
    use RefreshDatabase;

    private const RC_ORDER = '127547539';

    private function transferAction($status, $description = '')
    {
        return DomainActivity::normalize([
            'eaqid' => '5001',
            'entityid' => self::RC_ORDER,
            'actiontype' => 'AddTransferDomain',
            'actiontypedesc' => 'Transfer of netsgoods.com from old Registrar along with 1 year Renewal',
            'actionstatus' => $status,
            'actionstatusdesc' => $description,
            'actionadded' => '1793859165',
            'actionstarted' => '1793859165',
            'actioncompleted' => $status === 'Pending' ? '' : '1794308804',
        ]);
    }

    /** A ResellerClub stand-in that reports the given actions / details. */
    private function resellerClub($actions, $details, $pending = null)
    {
        $client = \Mockery::mock(ResellerClubClient::class);
        $client->shouldReceive('isConfigured')->andReturn(true);
        // null = the v2 pending list could not be read, so the order's own actions/details decide
        $client->shouldReceive('pendingTransfers')->andReturn($pending);
        $client->shouldReceive('domainOrderId')->andReturn(self::RC_ORDER);
        $client->shouldReceive('domainActions')->andReturn($actions);
        $client->shouldReceive('domainDetails')->andReturn($details);
        $client->shouldReceive('nameservers')->andReturn(['dns1.sanjaybd.com', 'dns2.sanjaybd.com']);
        $this->app->instance(ResellerClubClient::class, $client);

        return $client;
    }

    private function user()
    {
        return User::create([
            'name' => 'Transfer Tester',
            'email' => 'transfer-' . uniqid() . '@example.test',
            'password' => Hash::make('SecretP@ss1'),
            'email_verified_at' => now(),
        ]);
    }

    private function customerSession(User $user)
    {
        return ['customer' => [
            'id' => $user->id, 'uid' => 'CT-000001', 'name' => $user->name, 'initials' => 'TT',
            'email' => $user->email, 'phone' => '01700000000', 'balance' => 0, 'logo' => null, 'plan' => 'BDIX Premium',
        ]];
    }

    private function submittedTransfer(User $user, $reference = 'SYNC-ORDER', $domain = 'netsgoods.com')
    {
        $order = DomainOrder::create([
            'reference' => $reference,
            'user_id' => $user->id,
            'total' => 1493,
            'status' => 'transfer_pending',
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'provider' => 'bkash',
            'customer_name' => $user->name,
            'customer_phone' => '01700000000',
            'customer_email' => $user->email,
        ]);
        $item = $order->items()->create([
            'domain' => $domain,
            'tld' => 'com',
            'years' => 1,
            'price' => 1493,
            'renewal_price' => 1399,
            'order_type' => 'transfer',
            'registration_status' => 'transfer_submitted',
            'transfer_auth_code' => 'encrypted-placeholder',
        ]);
        app(InvoiceService::class)->createForOrder($order->fresh());

        return [$order->fresh(), $item->fresh()];
    }

    public function testFinishedTransferBecomesTransferredAndTheOrderActive()
    {
        [$order, $item] = $this->submittedTransfer($this->user());
        $this->resellerClub([$this->transferAction('Success')], ['currentstatus' => 'Active', 'endtime' => '1834272000']);

        $outcome = app(DomainTransferSyncService::class)->syncItem($item);

        $this->assertSame(DomainTransferSyncService::OUTCOME_COMPLETED, $outcome);
        $item = $item->fresh();
        $this->assertSame('transferred', $item->registration_status);
        $this->assertNull($item->transfer_auth_code);
        $this->assertSame(self::RC_ORDER, $item->reseller_order_id);
        $this->assertSame(1834272000, $item->expires_at->timestamp);
        $this->assertNotNull($item->transfer_completed_at);
        $this->assertSame('active', $order->fresh()->status);
    }

    public function testRejectedTransferBecomesFailedForAnAdminToHandle()
    {
        [$order, $item] = $this->submittedTransfer($this->user());
        $this->resellerClub([$this->transferAction('Failed', 'Rejected by the losing registrar')], ['currentstatus' => 'InActive']);

        $this->assertSame(DomainTransferSyncService::OUTCOME_FAILED, app(DomainTransferSyncService::class)->syncItem($item));

        $this->assertSame('transfer_failed', $item->fresh()->registration_status);
        $this->assertSame('transfer_failed', $order->fresh()->status);
    }

    public function testStillPendingAtTheRegistrarChangesNothing()
    {
        [$order, $item] = $this->submittedTransfer($this->user());
        $this->resellerClub([$this->transferAction('Pending')], ['currentstatus' => 'InActive']);

        $this->assertSame(DomainTransferSyncService::OUTCOME_PENDING, app(DomainTransferSyncService::class)->syncItem($item));

        $this->assertSame('transfer_submitted', $item->fresh()->registration_status);
        $this->assertSame('transfer_pending', $order->fresh()->status);
    }

    public function testAnUnreadableRegistrarIsNeverTreatedAsAnAnswer()
    {
        [$order, $item] = $this->submittedTransfer($this->user());
        $this->resellerClub(null, null);

        $this->assertSame(DomainTransferSyncService::OUTCOME_UNAVAILABLE, app(DomainTransferSyncService::class)->syncItem($item));

        $this->assertSame('transfer_submitted', $item->fresh()->registration_status);
        $this->assertSame('transfer_pending', $order->fresh()->status);
    }

    public function testOrderStaysPendingUntilEveryTransferOnItIsDone()
    {
        $user = $this->user();
        [$order, $first] = $this->submittedTransfer($user);
        $second = $order->items()->create([
            'domain' => 'second-domain.com', 'tld' => 'com', 'years' => 1, 'price' => 1493, 'renewal_price' => 1399,
            'order_type' => 'transfer', 'registration_status' => 'transfer_submitted',
        ]);
        $this->resellerClub([$this->transferAction('Success')], ['currentstatus' => 'Active']);

        app(DomainTransferSyncService::class)->syncItem($first);

        $this->assertSame('transferred', $first->fresh()->registration_status);
        $this->assertSame('transfer_pending', $order->fresh()->status);

        app(DomainTransferSyncService::class)->syncItem($second->fresh());

        $this->assertSame('active', $order->fresh()->status);
    }

    private function pendingList(array $rows)
    {
        $transfers = [];
        foreach ($rows as $domain => $orderId) {
            $transfers[$domain] = ['domain' => $domain, 'order_id' => $orderId, 'action_status' => 'Pending', 'added_on' => '', 'last_updated_on' => ''];
        }

        return ['transfers' => $transfers, 'total' => count($transfers), 'count' => count($transfers)];
    }

    public function testATransferListedAsPendingStaysPendingAndKeepsItsOrderId()
    {
        [$order, $item] = $this->submittedTransfer($this->user());
        $client = \Mockery::mock(ResellerClubClient::class);
        $client->shouldReceive('isConfigured')->andReturn(true);
        $client->shouldReceive('pendingTransfers')->with('netsgoods.com')->once()->andReturn($this->pendingList(['netsgoods.com' => self::RC_ORDER]));
        // listed as pending is the whole answer: no further ResellerClub calls
        $client->shouldNotReceive('domainOrderId');
        $client->shouldNotReceive('domainActions');
        $client->shouldNotReceive('domainDetails');
        $this->app->instance(ResellerClubClient::class, $client);

        $this->assertSame(DomainTransferSyncService::OUTCOME_PENDING, app(DomainTransferSyncService::class)->syncItem($item));

        $this->assertSame('transfer_submitted', $item->fresh()->registration_status);
        $this->assertSame(self::RC_ORDER, $item->fresh()->reseller_order_id);
    }

    public function testATransferMissingFromThePendingListIsCheckedAndCompletes()
    {
        [$order, $item] = $this->submittedTransfer($this->user());
        $this->resellerClub([$this->transferAction('Success')], ['currentstatus' => 'Active', 'endtime' => '1834272000'], $this->pendingList([]));

        $this->assertSame(DomainTransferSyncService::OUTCOME_COMPLETED, app(DomainTransferSyncService::class)->syncItem($item));

        $this->assertSame('transferred', $item->fresh()->registration_status);
        $this->assertSame('active', $order->fresh()->status);
    }

    public function testManyTransfersAreMatchedAgainstOneListCall()
    {
        $user = $this->user();
        [$orderA, $itemA] = $this->submittedTransfer($user, 'SYNC-A', 'still-pending.com');
        [$orderB, $itemB] = $this->submittedTransfer($user, 'SYNC-B', 'finished-one.com');
        $client = \Mockery::mock(ResellerClubClient::class);
        $client->shouldReceive('isConfigured')->andReturn(true);
        $client->shouldReceive('pendingTransfers')->with(null, 1, 100)->once()->andReturn($this->pendingList(['still-pending.com' => '555']));
        $client->shouldReceive('domainOrderId')->with('finished-one.com')->andReturn('777');
        $client->shouldReceive('domainActions')->with('777')->andReturn([$this->transferAction('Success')]);
        $client->shouldReceive('domainDetails')->with('777')->andReturn(['currentstatus' => 'Active']);
        $this->app->instance(ResellerClubClient::class, $client);

        $counts = app(DomainTransferSyncService::class)->syncAllPending();

        $this->assertSame([DomainTransferSyncService::OUTCOME_PENDING => 1, DomainTransferSyncService::OUTCOME_COMPLETED => 1], $counts);
        $this->assertSame('transfer_submitted', $itemA->fresh()->registration_status);
        $this->assertSame('transferred', $itemB->fresh()->registration_status);
    }

    public function testFinishedTransferIsManagedLikeARegisteredDomain()
    {
        $user = $this->user();
        [$order, $item] = $this->submittedTransfer($user);
        $client = $this->resellerClub([$this->transferAction('Success')], ['currentstatus' => 'Active', 'endtime' => '1834272000']);
        app(DomainTransferSyncService::class)->syncItem($item);
        $item = $item->fresh();
        $client->shouldReceive('modifyNameservers')->once()
            ->with(self::RC_ORDER, ['ns1.example.com', 'ns2.example.com'])
            ->andReturn(['status' => 'SUCCESS']);
        $session = $this->customerSession($user);

        $this->withSession($session)->get(route('mystore.domains.details', $item))
            ->assertOk()
            ->assertSee('DOMAIN TRANSFER')
            ->assertSee('TRANSFERRED')
            ->assertSee('dns1.sanjaybd.com')
            ->assertSee('Organization Name Consent');

        $this->withSession($session)->get(route('mystore.domains.activity', $item))
            ->assertOk()
            ->assertSee('Actions for Domain Transfer');

        $this->withSession($session)
            ->post(route('mystore.domains.nameservers', $item), ['nameservers' => "ns1.example.com\nns2.example.com"])
            ->assertRedirect(route('mystore.domains.details', $item))
            ->assertSessionHas('success', 'Nameserver updated successfully');
    }

    public function testAPendingTransferCannotBeManaged()
    {
        $user = $this->user();
        [$order, $item] = $this->submittedTransfer($user);
        $this->resellerClub([], null);

        $this->withSession($this->customerSession($user))
            ->get(route('mystore.domains.details', $item))
            ->assertNotFound();
    }

    public function testOpeningMyDomainsRefreshesPendingTransfersAndShowsManage()
    {
        $user = $this->user();
        [$order, $item] = $this->submittedTransfer($user);
        $this->resellerClub([$this->transferAction('Success')], ['currentstatus' => 'Active', 'endtime' => '1834272000']);

        $this->withSession($this->customerSession($user))->get('/mystore/domains')
            ->assertOk()
            ->assertSee('Success')
            ->assertSee('data-spinner-title="Loading domain details', false)
            ->assertSee(route('mystore.domains.details', $item), false);

        $this->assertSame('transferred', $item->fresh()->registration_status);
    }

    public function testMyDomainsShowsCheckStatusWithASpinnerWhileTheTransferIsPending()
    {
        $user = $this->user();
        [$order, $item] = $this->submittedTransfer($user);
        $this->resellerClub([$this->transferAction('Pending')], ['currentstatus' => 'InActive']);

        $this->withSession($this->customerSession($user))->get('/mystore/domains')
            ->assertOk()
            ->assertSee('Check status')
            ->assertSee('data-spinner="transfer"', false)
            ->assertSee('Checking transfer status', false)
            ->assertDontSee('data-spinner-title="Loading domain details', false);
    }

    public function testCheckStatusButtonOnlyWorksForTheOwner()
    {
        $owner = $this->user();
        [$order, $item] = $this->submittedTransfer($owner);
        $this->resellerClub([$this->transferAction('Success')], ['currentstatus' => 'Active']);

        $this->withSession($this->customerSession($this->user()))
            ->post(route('mystore.domains.transfer-sync', $item))
            ->assertNotFound();

        $this->withSession($this->customerSession($owner))
            ->post(route('mystore.domains.transfer-sync', $item))
            ->assertRedirect(route('mystore.domains'))
            ->assertSessionHas('success');

        $this->assertSame('transferred', $item->fresh()->registration_status);
    }

    public function testSchedulerCommandSyncsEveryPendingTransfer()
    {
        [$order, $item] = $this->submittedTransfer($this->user());
        $this->resellerClub([$this->transferAction('Success')], ['currentstatus' => 'Active']);

        $this->artisan('transfers:sync')->assertExitCode(0);

        $this->assertSame('transferred', $item->fresh()->registration_status);
        $this->assertSame('active', $order->fresh()->status);
    }

    public function testAdminOrdersPageRefreshesPendingTransfersSoCronIsOptional()
    {
        [$order, $item] = $this->submittedTransfer($this->user());
        $this->resellerClub([$this->transferAction('Success')], ['currentstatus' => 'Active'], $this->pendingList([]));

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get(route('admin.orders'))
            ->assertOk();

        $this->assertSame('transferred', $item->fresh()->registration_status);
        $this->assertSame('active', $order->fresh()->status);
    }

    public function testMyStoreHomeAndTheOrderPageAlsoRefreshPendingTransfers()
    {
        $user = $this->user();
        [$order, $item] = $this->submittedTransfer($user);
        $this->resellerClub([$this->transferAction('Success')], ['currentstatus' => 'Active'], $this->pendingList([]));

        $this->withSession($this->customerSession($user))->get('/mystore')->assertOk();

        $this->assertSame('transferred', $item->fresh()->registration_status);

        // the order page of a second, still-pending transfer refreshes it too
        [$order2, $item2] = $this->submittedTransfer($user, 'SYNC-ORDER-2', 'second-domain.com');
        $this->withSession($this->customerSession($user))
            ->get(route('domains.confirmation', $order2->reference))
            ->assertOk();

        $this->assertSame('transferred', $item2->fresh()->registration_status);
    }

    public function testAnUnknownExpiryOfATransferredDomainIsReadFromResellerClubNotGuessed()
    {
        $user = $this->user();
        [$order, $item] = $this->submittedTransfer($user);
        $order->update(['status' => 'active']);
        $item->update(['registration_status' => 'transferred', 'transfer_completed_at' => now(), 'reseller_order_id' => self::RC_ORDER]);
        // endtime 1834272000 = 15 Feb 2028; "transfer date + 1 year" would be Nov 2027
        $this->resellerClub([], ['currentstatus' => 'Active', 'endtime' => '1834272000']);
        $session = $this->customerSession($user);

        $this->withSession($session)->get('/mystore/domains')
            ->assertOk()
            ->assertSee('15 Feb 2028');

        $this->assertSame(1834272000, $item->fresh()->expires_at->timestamp);
    }

    public function testManualAdminCompletionAlsoUnlocksManagement()
    {
        $user = $this->user();
        [$order, $item] = $this->submittedTransfer($user);

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->post(route('admin.orders.status', $order), ['status' => 'active'])
            ->assertRedirect();

        $this->assertSame('transferred', $item->fresh()->registration_status);
        $this->assertTrue(DomainOrderItem::manageable()->whereKey($item->id)->exists());
    }
}
