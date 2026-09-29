<?php

namespace Tests\Feature;

use App\Services\BkashClient;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BkashCheckoutPaymentTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = new User([
            'name' => 'Pay Test',
            'email' => 'pay@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->user->email_verified_at = now();
        $this->user->save();

        config([
            'services.bkash.base_url' => 'https://checkout.pay.bka.sh/v1.2.0-beta',
            'services.bkash.app_key' => 'test-app-key',
            'services.bkash.app_secret' => 'test-app-secret',
            'services.bkash.username' => 'test-username',
            'services.bkash.password' => 'test-password',
        ]);
    }

    private function placeBkashOrder()
    {
        $this->post('/login', ['email' => 'pay@example.com', 'password' => 'password123']);
        $this->post('/domains/cart', ['domain' => 'example.com', 'price' => 1200]);
        $this->post('/domains/checkout', [
            'payment_method' => 'bkash',
            'full_name' => 'Pay Test',
            'phone' => '01712345678',
        ]);
    }

    /**
     * Fake client whose methods return the given canned responses while the
     * real controller logic (session/DB updates) runs.
     */
    private function fakeBkash(array $createResponse, array $executeResponse = [], array $queryResponse = [])
    {
        $fake = new class($createResponse, $executeResponse, $queryResponse) extends BkashClient {
            public $createResponse;
            public $executeResponse;
            public $queryResponse;
            public $createCalls = 0;
            public $executeCalls = 0;
            public $queryCalls = 0;

            public function __construct($createResponse, $executeResponse = [], $queryResponse = [])
            {
                $this->createResponse = $createResponse;
                $this->executeResponse = $executeResponse;
                $this->queryResponse = $queryResponse;
            }

            public function isConfigured()
            {
                return true;
            }

            public function createPayment($amount, $invoice, $payerReference = null)
            {
                $this->createCalls++;

                return $this->createResponse;
            }

            public function executePayment($paymentId)
            {
                $this->executeCalls++;

                return $this->executeResponse;
            }

            public function queryPayment($paymentId)
            {
                $this->queryCalls++;

                return $this->queryResponse;
            }
        };

        $this->app->instance(BkashClient::class, $fake);

        return $fake;
    }

    public function testPayPageRendersForBkashOrder()
    {
        $this->placeBkashOrder();

        $response = $this->get(route('bkash.pay', ['invoice' => session('last_domain_order.reference')]));

        $response->assertOk();
        $response->assertViewIs('domains.bkash-payment');
        $response->assertSee(session('last_domain_order.reference'));
    }

    public function testSuccessfulExecuteMarksOrderPaid()
    {
        $fake = $this->fakeBkash(
            ['paymentID' => 'TR0011ABC123_payment', 'amount' => '1200.00'],
            ['transactionStatus' => 'Completed', 'trxID' => 'TR0011ABC123', 'statusCode' => '0000']
        );

        $this->placeBkashOrder();
        $this->post(route('bkash.create'))->assertOk();
        $response = $this->post(route('bkash.execute'));

        $response->assertOk();
        $response->assertJson(['status' => 'success']);

        $order = session('last_domain_order');
        $this->assertSame('TR0011ABC123', $order['payment']['transaction_id']);

        $this->assertDatabaseHas('domain_orders', ['payment_status' => 'paid', 'provider_payment_id' => 'TR0011ABC123']);
        $this->assertDatabaseHas('payments', ['status' => 'paid', 'provider_payment_id' => 'TR0011ABC123']);
    }

    public function testCreateRecordsInitiatedAttempt()
    {
        $fake = $this->fakeBkash(['paymentID' => 'TR0011XYZ_payment', 'amount' => '1200.00']);

        $this->placeBkashOrder();
        $response = $this->post(route('bkash.create'));

        $response->assertOk();
        $response->assertJson(['paymentID' => 'TR0011XYZ_payment']);
        $this->assertSame('TR0011XYZ_payment', session('last_domain_order.payment.paymentID'));
        $this->assertDatabaseHas('domain_orders', ['payment_status' => 'initiated', 'provider_payment_id' => 'TR0011XYZ_payment']);
        $this->assertDatabaseHas('payments', ['status' => 'initiated', 'provider_payment_id' => 'TR0011XYZ_payment']);
    }

    public function testProgressingExecuteReturnsPending()
    {
        $fake = $this->fakeBkash(
            ['paymentID' => 'TR0011PND_payment', 'amount' => '1200.00'],
            ['transactionStatus' => 'Initiated', 'statusCode' => '2052']
        );

        $this->placeBkashOrder();
        $this->post(route('bkash.create'));
        $response = $this->post(route('bkash.execute'));

        $response->assertOk();
        $response->assertJson(['status' => 'pending']);
        $this->assertDatabaseHas('domain_orders', ['payment_status' => 'initiated']);
    }

    public function testFailedExecuteReturnsErrorWithoutMarkingPaid()
    {
        $fake = $this->fakeBkash(
            ['paymentID' => 'TR0011ERR_payment', 'amount' => '1200.00'],
            ['statusMessage' => 'Insufficient balance', 'statusCode' => '5001']
        );

        $this->placeBkashOrder();
        $this->post(route('bkash.create'));
        $response = $this->post(route('bkash.execute'));

        $response->assertStatus(402);
        $response->assertJson(['errorMessage' => 'Payment was not completed: Insufficient balance']);
        $this->assertDatabaseHas('domain_orders', ['payment_status' => 'initiated']);
    }

    public function testCompletedQueryMarksOrderPaid()
    {
        $fake = $this->fakeBkash(
            ['paymentID' => 'TR0011QRY_payment', 'amount' => '1200.00'],
            [],
            ['transactionStatus' => 'Completed', 'trxID' => 'TR0011QRY999']
        );

        $this->placeBkashOrder();
        $this->post(route('bkash.create'));
        $response = $this->post(route('bkash.query'));

        $response->assertOk();
        $response->assertJson(['status' => 'success']);
        $this->assertDatabaseHas('domain_orders', ['payment_status' => 'paid', 'provider_payment_id' => 'TR0011QRY999']);
    }

    public function testUnconfiguredGatewayReturns503()
    {
        config(['services.bkash.app_key' => null]);
        $this->app->forgetInstance(BkashClient::class);

        $this->post('/login', ['email' => 'pay@example.com', 'password' => 'password123']);
        $this->post('/domains/cart', ['domain' => 'example.com', 'price' => 1200]);
        $this->post('/domains/checkout', [
            'payment_method' => 'bkash',
            'full_name' => 'Pay Test',
            'phone' => '01712345678',
        ]);

        $response = $this->post(route('bkash.create'));
        $response->assertStatus(503);
        $response->assertJson(['errorMessage' => 'bKash is not configured on this server yet. Our billing team will contact you with payment instructions for reference ' . session('last_domain_order.reference') . '.']);

        $this->assertDatabaseHas('domain_orders', ['payment_status' => 'pending']);
    }

    public function testCreateWithoutBkashOrderIsRejected()
    {
        $this->post('/login', ['email' => 'pay@example.com', 'password' => 'password123']);

        $this->post(route('bkash.create'))->assertStatus(409);
    }

    public function testExecuteWithoutActivePaymentIsRejected()
    {
        $this->post('/login', ['email' => 'pay@example.com', 'password' => 'password123']);

        $this->post(route('bkash.execute'))->assertStatus(409);
    }
}
