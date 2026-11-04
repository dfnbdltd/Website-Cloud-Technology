<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.driver' => 'log', 'queue.default' => 'sync']);

        Schema::create('email_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key_name')->unique();
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('email_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('recipient');
            $table->string('template_key')->nullable();
            $table->string('subject')->nullable();
            $table->string('status')->default('queued');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('email_verification_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        \DB::table('email_templates')->insert([
            'key_name' => 'email_verification',
            'subject' => 'Verify {{name}}',
            'body' => 'Verify here: {{link}}',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_customer_with_post_request()
    {
        $customer = User::create([
            'name' => 'Delete Me',
            'email' => 'delete-me@example.test',
            'password' => 'secret',
        ]);

        $response = $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->post(route('admin.customers.delete', $customer));

        $response->assertRedirect(route('admin.customers'));
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }

    public function test_customer_list_uses_styled_delete_confirmation()
    {
        User::create([
            'name' => 'Confirmation Customer',
            'email' => 'confirmation@example.test',
            'password' => 'secret',
        ]);

        $response = $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get(route('admin.customers'));

        $response->assertOk()
            ->assertSee('admin-confirm-modal')
            ->assertSee('Yes, delete customer')
            ->assertSee('method="POST"', false);
    }

    public function test_guest_cannot_delete_customer()
    {
        $customer = User::create([
            'name' => 'Protected Customer',
            'email' => 'protected@example.test',
            'password' => 'secret',
        ]);

        $response = $this->post(route('admin.customers.delete', $customer));

        $response->assertRedirect(route('admin.login'));
        $this->assertDatabaseHas('users', ['id' => $customer->id]);
    }

    public function test_admin_can_resend_verification_to_unverified_customer()
    {
        $customer = User::create([
            'name' => 'Unverified Customer',
            'first_name' => 'Unverified',
            'email' => 'unverified@example.test',
            'password' => 'secret',
        ]);

        $response = $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->from(route('admin.customers'))
            ->post(route('admin.customers.resend-verification', $customer));

        $response->assertRedirect(route('admin.customers'));
        $this->assertDatabaseHas('email_verification_tokens', [
            'user_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('email_logs', [
            'recipient' => $customer->email,
            'template_key' => 'email_verification',
            'status' => 'sent',
        ]);
    }

    public function test_admin_cannot_resend_verification_to_verified_customer()
    {
        $customer = User::create([
            'name' => 'Verified Customer',
            'email' => 'verified@example.test',
            'password' => 'secret',
        ]);
        $customer->forceFill(['email_verified_at' => now()])->save();

        $response = $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->from(route('admin.customers'))
            ->post(route('admin.customers.resend-verification', $customer));

        $response->assertRedirect(route('admin.customers'));
        $response->assertSessionHasErrors('customer');
        $this->assertDatabaseMissing('email_verification_tokens', ['user_id' => $customer->id]);
    }
}
