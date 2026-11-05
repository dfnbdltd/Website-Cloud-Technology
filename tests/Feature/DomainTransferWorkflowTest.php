<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\DomainOrderItem;
use App\Services\ResellerClubClient;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class DomainTransferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function testTransferLandingShowsRequirementsAndSubmitsToCheckout()
    {
        $this->get(route('services.coming-soon', ['service' => 'domain-transfer']))
            ->assertOk()
            ->assertSee('Domain Transfer Procedure')
            ->assertSee('Pre-requirements to Transfer a Domain to Cloud Technology')
            ->assertSee('clientTransferProhibited')
            ->assertSee('60-Day Rule:')
            ->assertSee('After Submitting Transfer Procedure')
            ->assertSee('Continue to checkout')
            ->assertSee(route('domains.transfer'), false)
            ->assertDontSee('Transfer request received');
    }

    public function testPaidTransferIsSubmittedToResellerClubAndEppCodeIsEncrypted()
    {
        config([
            'services.resellerclub.customer_id' => 'reseller-customer',
            'services.resellerclub.reg_contact_id' => 'registration-contact',
            'services.resellerclub.admin_contact_id' => 'admin-contact',
            'services.resellerclub.tech_contact_id' => 'tech-contact',
            'services.resellerclub.billing_contact_id' => 'billing-contact',
            'services.resellerclub.price_currency' => 'BDT',
        ]);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('validateDomainTransferIn')
            ->twice()
            ->with('example.com')
            ->andReturn(true);
        $resellerClub->shouldReceive('isTransferConfiguredForDomain')
            ->once()
            ->with('example.com')
            ->andReturn(true);
        $resellerClub->shouldReceive('liveTransferPrice')->twice()->with('com')->andReturn(1600.0);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldReceive('walletBalance')->once()->andReturn([
            'balance' => 10000.0,
            'currency' => 'BDT',
        ]);
        $resellerClub->shouldReceive('transferDomain')
            ->once()
            ->with('example.com', 'epp-secret-123')
            ->andReturn([
                'status' => 'Success',
                'actionStatus' => 'Success',
                'entityId' => '12345',
            ]);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $user = User::create([
            'name' => 'Transfer Customer',
            'email' => 'transfer@example.test',
            'password' => bcrypt('password123'),
            'phone' => '01700000000',
            'email_verified_at' => now(),
        ]);
        $user->balance = 5000;
        $user->save();
        $session = ['customer' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'balance' => 5000,
        ]];

        $this->withSession($session)->post(route('domains.transfer'), [
            'domain' => 'Example.com',
            'auth_code' => 'epp-secret-123',
        ])->assertRedirect(route('domains.checkout'));

        $cart = session('domain_cart');
        $this->assertSame(1600.0, $cart['example.com']['price']);
        $this->assertNotSame('epp-secret-123', $cart['example.com']['transfer_auth_code']);
        $this->get(route('domains.checkout'))
            ->assertOk()
            ->assertSee('Transfer eligibility checked; please verify your EPP code.')
            ->assertSee('The registry checks the EPP code when the transfer is submitted after payment.');

        $this->post(route('domains.place-order'), [
            'payment_method' => 'wallet',
            'purchase_for' => 'myself',
        ])->assertOk()
            ->assertViewIs('domains.confirmation')
            ->assertViewHas('transferPending', true);

        $order = DomainOrder::with('items')->firstOrFail();
        $item = $order->items->first();

        $this->assertSame('transfer_pending', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('transfer', $item->order_type);
        $this->assertSame('transfer_submitted', $item->registration_status);
        $this->assertNull($item->transfer_auth_code);
        $this->assertStringNotContainsString('epp-secret-123', $order->payload);
        $this->assertSame(3400.0, (float) $user->fresh()->balance);
    }

    public function testInvalidTransferInputDoesNotFlashEppCodeIntoSession()
    {
        $this->withSession([])->from(route('landing'))->post(route('domains.transfer'), [
            'domain' => 'not a domain',
            'auth_code' => 'sensitive-epp-code',
        ])->assertRedirect(route('landing'))
            ->assertSessionHasErrors(['domain'])
            ->assertSessionMissing('_old_input.auth_code');
    }

    public function testMalformedGenericTldAuthCodeIsRejectedBeforeCheckout()
    {
        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldNotReceive('validateDomainTransferIn');
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->withSession([])->from(route('landing'))->post(route('domains.transfer'), [
            'domain' => 'example.com',
            'auth_code' => 'invalid-epp',
        ])->assertRedirect(route('landing'))
            ->assertSessionHasErrors(['auth_code'])
            ->assertSessionMissing('_old_input.auth_code');
    }

    public function testLockedDomainIsRejectedBeforeCheckout()
    {
        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('validateDomainTransferIn')
            ->once()
            ->with('example.com')
            ->andReturn(false);
        $resellerClub->shouldNotReceive('liveTransferPrice');
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->withSession([])->from(route('landing'))->post(route('domains.transfer'), [
            'domain' => 'example.com',
            'auth_code' => 'epp-secret-123',
        ])->assertRedirect(route('landing'))
            ->assertSessionHasErrors(['domain'])
            ->assertSessionMissing('_old_input.auth_code');
    }

    public function testTransferIsRejectedWhenEligibilityCannotBeVerified()
    {
        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('validateDomainTransferIn')
            ->once()
            ->with('example.com')
            ->andReturn(null);
        $resellerClub->shouldNotReceive('liveTransferPrice');
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->withSession([])->from(route('landing'))->post(route('domains.transfer'), [
            'domain' => 'example.com',
            'auth_code' => 'epp-secret-123',
        ])->assertRedirect(route('landing'))
            ->assertSessionHasErrors(['domain'])
            ->assertSessionMissing('_old_input.auth_code');
    }

    public function testCheckoutPreflightStopsPaymentWhenDomainIsNoLongerEligible()
    {
        config([
            'services.resellerclub.customer_id' => 'reseller-customer',
            'services.resellerclub.reg_contact_id' => 'registration-contact',
            'services.resellerclub.admin_contact_id' => 'admin-contact',
            'services.resellerclub.tech_contact_id' => 'tech-contact',
            'services.resellerclub.billing_contact_id' => 'billing-contact',
        ]);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldReceive('isTransferConfiguredForDomain')
            ->once()
            ->with('example.com')
            ->andReturn(true);
        $resellerClub->shouldReceive('validateDomainTransferIn')
            ->once()
            ->with('example.com')
            ->andReturn(false);
        $resellerClub->shouldNotReceive('liveTransferPrice');
        $resellerClub->shouldNotReceive('walletBalance');
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $error = $this->app->make(\App\Services\DomainTransferService::class)
            ->preflightError([
                'example.com' => [
                    'domain' => 'example.com',
                    'order_type' => 'transfer',
                    'price' => 1600,
                ],
            ]);

        $this->assertStringContainsString('no payment has been taken', strtolower($error));
    }

    public function testFailedTransferCanBeRetriedByAnAdmin()
    {
        $order = DomainOrder::create([
            'reference' => 'CT-TRANSFER-FAIL',
            'total' => 1600,
            'status' => 'paid',
            'payment_method' => 'bank',
            'payment_status' => 'paid',
            'provider' => 'manual',
            'customer_name' => 'Transfer Customer',
            'customer_phone' => '01700000000',
            'customer_email' => 'transfer@example.test',
        ]);
        DomainOrderItem::create([
            'domain_order_id' => $order->id,
            'domain' => 'example.com',
            'tld' => 'com',
            'years' => 1,
            'price' => 1600,
            'renewal_price' => 1600,
            'order_type' => 'transfer',
            'transfer_auth_code' => Crypt::encryptString('invalid-epp'),
        ]);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('transferDomain')
            ->twice()
            ->with('example.com', 'invalid-epp')
            ->andReturn(
                ['status' => 'ERROR', 'message' => 'Authorization code rejected'],
                ['status' => 'Success']
            );
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $service = $this->app->make(\App\Services\DomainTransferService::class);
        $service->transferPaidOrder($order);
        $service->transferPaidOrder($order->fresh());

        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'status' => 'transfer_failed',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('domain_order_items', [
            'domain_order_id' => $order->id,
            'registration_status' => 'transfer_failed',
        ]);
        $storedCode = DomainOrderItem::where('domain_order_id', $order->id)->value('transfer_auth_code');
        $this->assertNotNull($storedCode);
        $this->assertSame('invalid-epp', Crypt::decryptString($storedCode));

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get(route('admin.orders'))
            ->assertOk()
            ->assertSee('Retry transfer')
            ->assertDontSee('name="auth_codes[');

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->post(route('admin.orders.retry-transfer', $order))
            ->assertRedirect(route('admin.orders'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'status' => 'transfer_pending',
        ]);
        $this->assertDatabaseHas('domain_order_items', [
            'domain_order_id' => $order->id,
            'registration_status' => 'transfer_submitted',
            'transfer_auth_code' => null,
        ]);
    }

    public function testAdminCanProvideEppCodeWhenRetryingAnOlderFailedTransfer()
    {
        $order = DomainOrder::create([
            'reference' => 'CT-TRANSFER-OLD-FAIL',
            'total' => 1600,
            'status' => 'transfer_failed',
            'payment_method' => 'bank',
            'payment_status' => 'paid',
            'provider' => 'manual',
            'customer_name' => 'Transfer Customer',
            'customer_phone' => '01700000000',
            'customer_email' => 'transfer@example.test',
        ]);
        $item = DomainOrderItem::create([
            'domain_order_id' => $order->id,
            'domain' => 'example.com',
            'tld' => 'com',
            'years' => 1,
            'price' => 1600,
            'renewal_price' => 1600,
            'order_type' => 'transfer',
            'registration_status' => 'transfer_failed',
            'transfer_auth_code' => null,
        ]);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('transferDomain')
            ->once()
            ->with('example.com', 'replacement-epp')
            ->andReturn(['status' => 'Success']);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $adminSession = ['admin' => ['email' => 'admin@example.test']];
        $this->withSession($adminSession)
            ->get(route('admin.orders'))
            ->assertOk()
            ->assertSee('name="auth_codes[' . $item->id . ']"', false);

        $this->withSession($adminSession)
            ->post(route('admin.orders.retry-transfer', $order), [
                'auth_codes' => [$item->id => 'replacement-epp'],
            ])
            ->assertRedirect(route('admin.orders'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'status' => 'transfer_pending',
        ]);
        $this->assertDatabaseHas('domain_order_items', [
            'id' => $item->id,
            'registration_status' => 'transfer_submitted',
            'transfer_auth_code' => null,
        ]);
    }

    public function testTransferProcessingOrderIsNeverAutomaticallyResubmitted()
    {
        $order = DomainOrder::create([
            'reference' => 'CT-TRANSFER-PROCESSING',
            'total' => 1600,
            'status' => 'transfer_processing',
            'payment_method' => 'bank',
            'payment_status' => 'paid',
            'provider' => 'manual',
            'customer_name' => 'Transfer Customer',
            'customer_phone' => '01700000000',
            'customer_email' => 'transfer@example.test',
        ]);
        DomainOrderItem::create([
            'domain_order_id' => $order->id,
            'domain' => 'example.com',
            'tld' => 'com',
            'years' => 1,
            'price' => 1600,
            'renewal_price' => 1600,
            'order_type' => 'transfer',
            'transfer_auth_code' => Crypt::encryptString('secure-epp-123!'),
        ]);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldNotReceive('transferDomain');
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->app->make(\App\Services\DomainTransferService::class)
            ->transferPaidOrder($order->fresh());

        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'status' => 'transfer_processing',
        ]);
    }

    public function testTransferSubmissionUsesResellerClubV2JsonApi()
    {
        config([
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'reseller-user-123',
            'services.resellerclub.api_key' => 'api-key-secret',
            'services.resellerclub.v2_base_url' => 'https://api.resellerclub.com/v2',
            'services.resellerclub.customer_id' => '14569999',
            'services.resellerclub.reg_contact_id' => '101',
            'services.resellerclub.admin_contact_id' => '102',
            'services.resellerclub.tech_contact_id' => '103',
            'services.resellerclub.billing_contact_id' => '104',
        ]);

        $client = new class extends ResellerClubClient {
            public $request;

            protected function v2Request($method, $path, array $payload = [])
            {
                $this->request = [$method, $path, $payload];

                return ['status' => 'Success'];
            }
        };

        $this->assertSame(['status' => 'Success'], $client->transferDomain('Example.com', 'secure-epp-123!'));
        $this->assertSame([
            'POST',
            '/domains/example.com/transfer-in',
            [
                'authCode' => 'secure-epp-123!',
                'customerId' => 14569999,
                'invoiceOption' => 'PayInvoice',
                'autoRenew' => false,
                'regContactId' => 101,
                'adminContactId' => 102,
                'techContactId' => 103,
                'billingContactId' => 104,
            ],
        ], $client->request);
    }

    public function testTransferSubmissionDoesNotCallApiWithInvalidContactIds()
    {
        config([
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'reseller-user-123',
            'services.resellerclub.api_key' => 'api-key-secret',
            'services.resellerclub.v2_base_url' => 'https://api.resellerclub.com/v2',
            'services.resellerclub.customer_id' => '14569999',
            'services.resellerclub.reg_contact_id' => 'not-a-contact-id',
            'services.resellerclub.admin_contact_id' => '102',
            'services.resellerclub.tech_contact_id' => '103',
            'services.resellerclub.billing_contact_id' => '104',
        ]);

        $client = new class extends ResellerClubClient {
            public $requestMade = false;

            protected function v2Request($method, $path, array $payload = [])
            {
                $this->requestMade = true;

                return null;
            }
        };

        $this->assertNull($client->transferDomain('example.com', 'secure-epp-123!'));
        $this->assertFalse($client->requestMade);
    }

    public function testTransferFailureExcerptRedactsApiCredentialsAndEppCodes()
    {
        $client = new ResellerClubClient();
        $method = new \ReflectionMethod(ResellerClubClient::class, 'safeResponseExcerpt');
        $method->setAccessible(true);

        $excerpt = $method->invoke(
            $client,
            '<html><title>Forbidden apiKey=private-key authCode=private-epp</title><p>hidden-private-epp</p></html>'
        );

        $this->assertStringContainsString('Forbidden', $excerpt);
        $this->assertStringContainsString('apiKey=[redacted]', $excerpt);
        $this->assertStringContainsString('authCode=[redacted]', $excerpt);
        $this->assertStringNotContainsString('private-key', $excerpt);
        $this->assertStringNotContainsString('private-epp', $excerpt);
        $this->assertStringNotContainsString('hidden-private-epp', $excerpt);
    }

    public function testTransferPriceIsFetchedFromResellerClubCustomerPriceResponse()
    {
        config([
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'test-reseller',
            'services.resellerclub.api_key' => 'test-api-key',
            'services.resellerclub.price_currency' => 'BDT',
        ]);
        \Illuminate\Support\Facades\Cache::put('resellerclub.pricing.v2', [
            'domcno' => ['addtransferdomain' => ['1' => 1600]],
            'dotnet' => ['addtransferdomain' => ['1' => 2100]],
        ], 60);

        $client = new ResellerClubClient();

        $this->assertSame(1600.0, $client->liveTransferPrice('com'));
        $this->assertSame(2100.0, $client->liveTransferPrice('net'));
        $this->assertNull($client->liveTransferPrice('org'));
    }
}
