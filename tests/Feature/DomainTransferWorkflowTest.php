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
            ->assertSee('Prepare your domain for transfer')
            ->assertSee('clientTransferProhibited')
            ->assertSee('60-day rule')
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
        $resellerClub->shouldReceive('liveTransferPrice')->twice()->with('com')->andReturn(1600.0);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldReceive('walletBalance')->once()->andReturn([
            'balance' => 10000.0,
            'currency' => 'BDT',
        ]);
        $resellerClub->shouldReceive('transferDomain')
            ->once()
            ->with('example.com', 'epp-secret-123')
            ->andReturn(['actionstatus' => 'NoError', 'entityid' => '12345']);
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

    public function testRejectedTransferIsMarkedFailedAndIsNotAutomaticallyRetried()
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
            ->once()
            ->with('example.com', 'invalid-epp')
            ->andReturn(['status' => 'ERROR', 'message' => 'Authorization code rejected']);
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
