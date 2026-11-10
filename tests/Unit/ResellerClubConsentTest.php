<?php

namespace Tests\Unit;

use App\Services\ResellerClubClient;
use Tests\TestCase;

class ResellerClubConsentTest extends TestCase
{
    /** @test */
    public function consent_request_uses_resellerclub_modify_contact_contract()
    {
        config([
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'user-123',
            'services.resellerclub.api_key' => 'key-123',
            'services.resellerclub.base_url' => 'https://httpapi.com/api',
            'services.resellerclub.reg_contact_id' => '10',
            'services.resellerclub.admin_contact_id' => '20',
            'services.resellerclub.tech_contact_id' => '30',
            'services.resellerclub.billing_contact_id' => '40',
        ]);

        $client = new class extends ResellerClubClient {
            public $captured;

            protected function request(
                $path,
                array $parameters = [],
                $method = 'GET',
                $allowScalar = false,
                $ipv4Only = false,
                $requireSuccessStatus = false
            )
            {
                $this->captured = compact('path', 'parameters', 'method');
                return ['status' => 'SUCCESS'];
            }
        };

        $this->assertSame(
            ['status' => 'SUCCESS'],
            $client->modifyRegistrantOrganizationConsent('example.com', '127547539', true)
        );
        $this->assertSame('/domains/modify-contact.json', $client->captured['path']);
        $this->assertSame('POST', $client->captured['method']);
        $this->assertSame('127547539', $client->captured['parameters']['order-id']);
        $this->assertSame('registrant-org-consent', $client->captured['parameters']['attr-name1']);
        $this->assertSame('true', $client->captured['parameters']['attr-value1']);
        $this->assertSame('10', $client->captured['parameters']['reg-contact-id']);
        $this->assertSame('20', $client->captured['parameters']['admin-contact-id']);
        $this->assertSame('30', $client->captured['parameters']['tech-contact-id']);
        $this->assertSame('40', $client->captured['parameters']['billing-contact-id']);

        $client->modifyRegistrantOrganizationConsent('example.com', '127547539', false);
        $this->assertSame('false', $client->captured['parameters']['attr-value1']);
    }
}
