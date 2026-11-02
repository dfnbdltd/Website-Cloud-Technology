<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\DomainOrderItem;
use App\Services\DomainRegistrationService;
use App\Services\ResellerClubClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrder()
    {
        $order = DomainOrder::create([
            'reference' => 'CT-REG-001',
            'total' => 1800,
            'status' => 'paid',
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'provider' => 'bkash',
            'customer_name' => 'Test Customer',
            'customer_phone' => '01700000000',
            'customer_email' => 'customer@example.test',
        ]);
        DomainOrderItem::create([
            'domain_order_id' => $order->id,
            'domain' => 'example.com',
            'tld' => 'com',
            'years' => 1,
            'price' => 1800,
            'renewal_price' => 1800,
            'purchase_price' => 1800,
        ]);

        return $order;
    }

    public function testPaidOrderIsRegisteredOnceThroughResellerClub()
    {
        config(['services.resellerclub.auto_register' => true]);
        config(['services.test_domains' => []]);
        $order = $this->paidOrder();

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('registerDomain')
            ->once()
            ->with('example.com', 1)
            ->andReturn(['status' => 'Success']);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $service = $this->app->make(DomainRegistrationService::class);
        $service->registerPaidOrder($order);
        $service->registerPaidOrder($order->fresh());

        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'active',
        ]);
    }

    public function testFailedResellerClubRegistrationRemainsPending()
    {
        config(['services.resellerclub.auto_register' => true]);
        config(['services.test_domains' => []]);
        $order = $this->paidOrder();

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('registerDomain')
            ->once()
            ->with('example.com', 1)
            ->andReturn(['status' => 'ERROR', 'message' => 'Insufficient funds']);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->app->make(DomainRegistrationService::class)->registerPaidOrder($order);

        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'registration_pending',
        ]);
    }
}
