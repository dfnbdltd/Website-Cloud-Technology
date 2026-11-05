<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class TurnstileVerificationTest extends TestCase
{
    public function testRegistrationPageRendersWidgetWithoutExposingSecret()
    {
        config([
            'services.turnstile.site_key' => 'site-key-for-test',
            'services.turnstile.secret_key' => 'secret-key-for-test',
        ]);

        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('class="cf-turnstile"', false)
            ->assertSee('data-sitekey="site-key-for-test"', false)
            ->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', false)
            ->assertDontSee('secret-key-for-test', false);
    }

    public function testMissingTokenIsRejectedWithoutCallingCloudflare()
    {
        config([
            'services.turnstile.secret_key' => 'test-secret',
        ]);

        $controller = new AuthController();
        $method = new \ReflectionMethod($controller, 'turnstileIsValid');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, Request::create('/register', 'POST')));
    }

    public function testCloudflareResponseControlsRegistrationVerification()
    {
        config([
            'services.turnstile.secret_key' => 'test-secret',
        ]);

        $client = Mockery::mock();
        $responses = [
            $this->responseWithBody(json_encode([
                'success' => false,
                'error-codes' => ['timeout-or-duplicate'],
            ])),
            $this->responseWithBody(json_encode(['success' => true])),
        ];
        $client->shouldReceive('post')
            ->twice()
            ->with(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                Mockery::on(function (array $options) {
                    return $options['form_params']['secret'] === 'test-secret'
                        && $options['form_params']['response'] === 'token-a'
                        && isset($options['form_params']['remoteip']);
                })
            )
            ->andReturnUsing(function () use (&$responses) {
                return array_shift($responses);
            });

        $controller = new AuthController($client);
        $method = new \ReflectionMethod($controller, 'turnstileIsValid');
        $method->setAccessible(true);

        $server = ['REMOTE_ADDR' => '127.0.0.1'];
        $rejected = Request::create('/register', 'POST', [
            'cf-turnstile-response' => 'token-a',
        ], [], [], $server);
        $accepted = Request::create('/register', 'POST', [
            'cf-turnstile-response' => 'token-a',
        ], [], [], $server);

        $this->assertFalse($method->invoke($controller, $rejected));
        $this->assertTrue($method->invoke($controller, $accepted));
    }

    private function responseWithBody($body)
    {
        return new class($body)
        {
            private $body;

            public function __construct($body)
            {
                $this->body = $body;
            }

            public function getBody()
            {
                return $this->body;
            }
        };
    }
}
