<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\EmailVerificationToken;
use App\Models\MailConfig;
use App\Services\DiagnosticSmtp;
use App\Services\MailDispatcher;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\PHPMailer;
use Tests\TestCase;

/**
 * Guide sections 3-7: mail_configs, email_templates, email_logs,
 * one-time verification tokens and the queued MailDispatcher send.
 *
 * The four tables come from database/email_system.sql (no migration in this
 * project), so they are created here with SQLite-compatible columns.
 */
class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Never talk to a real SMTP server from the test suite.
        config(['mail.driver' => 'log']);

        $this->createEmailTables();

        DB::table('email_templates')->insert([
            [
                'key_name' => 'email_verification',
                'subject' => 'Verify your email, {{name}}',
                'body' => 'Hello {{name}}, please verify: {{link}}',
                'is_active' => 1,
            ],
            [
                'key_name' => 'account_ready',
                'subject' => 'Your account is ready — login details',
                'body' => 'Hi {{name}}, customer id {{customer_id}} login {{login_url}}',
                'is_active' => 1,
            ],
        ]);
    }

    public function testRegistrationStoresTokenAndLogsSentMail()
    {
        $response = $this->post('/register', $this->validRegistration());

        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'token-flow@example.test')->first();
        $this->assertNotNull($user, 'user should be created');
        $this->assertNull($user->email_verified_at);

        $token = EmailVerificationToken::where('user_id', $user->id)->first();
        $this->assertNotNull($token, 'verification token should be stored');
        $this->assertSame(64, strlen($token->token));
        $this->assertNull($token->used_at);
        $this->assertTrue($token->expires_at->greaterThan(now()));
        $this->assertTrue($token->expires_at->lessThan(now()->addHours(2)));

        $log = EmailLog::where('recipient', 'token-flow@example.test')
            ->where('template_key', 'email_verification')
            ->first();
        $this->assertNotNull($log, 'email_logs row should exist');
        $this->assertSame('sent', $log->status);
        // {{name}} placeholder replaced from the registration data.
        $this->assertSame('Verify your email, Test', $log->subject);
    }

    public function testSmtpVerificationMailUsesPhpMailerWithConfiguredStartTls()
    {
        config([
            'mail.driver' => 'smtp',
            'mail.host' => 'smtp.gmail.com',
            'mail.port' => 587,
            'mail.username' => 'sender@example.test',
            'mail.password' => 'test app password',
            'mail.encryption' => 'tls',
            'mail.from.address' => 'sender@example.test',
            'mail.from.name' => 'CloudTech',
        ]);

        $mailer = new class(true) extends PHPMailer {
            public function send()
            {
                return true;
            }
        };
        $dispatcher = new class($mailer) extends MailDispatcher {
            private $mailer;

            public function __construct(PHPMailer $mailer)
            {
                $this->mailer = $mailer;
            }

            protected function createPhpMailer(): PHPMailer
            {
                return $this->mailer;
            }
        };

        $this->assertTrue($dispatcher->sendByCompany(0, 'email_verification', 'user@example.test', [
            'name' => 'Test',
            'link' => 'https://example.test/verify',
        ]));

        $this->assertSame('smtp.gmail.com', $mailer->Host);
        $this->assertSame(587, $mailer->Port);
        $this->assertTrue($mailer->SMTPAuth);
        $this->assertSame('sender@example.test', $mailer->Username);
        $this->assertSame('testapppassword', $mailer->Password);
        $this->assertSame(PHPMailer::ENCRYPTION_STARTTLS, $mailer->SMTPSecure);
        $this->assertSame('Verify your email, Test', $mailer->Subject);
        $this->assertSame('Hello Test, please verify: https://example.test/verify', $mailer->Body);
    }

    public function testSmtpFailureIncludesServerReplyWithoutExposingCredentials()
    {
        config([
            'mail.driver' => 'smtp',
            'mail.host' => 'smtp.gmail.com',
            'mail.port' => 587,
            'mail.username' => 'sender@example.test',
            'mail.password' => 'secret-app-password',
            'mail.encryption' => 'tls',
            'mail.from.address' => 'sender@example.test',
            'mail.from.name' => 'CloudTech',
        ]);

        $mailer = new class(true) extends PHPMailer {
            public function send()
            {
                throw new \RuntimeException('SMTP Error: Could not authenticate.');
            }
        };
        $smtp = new class extends DiagnosticSmtp {
            public function simulateAuthenticationFailure(string $reply)
            {
                $this->recordAuthenticationFailure($reply, []);
            }
        };
        $smtp->simulateAuthenticationFailure("535-5.7.8 Username and Password not accepted\r\n");
        $dispatcher = new class($mailer, $smtp) extends MailDispatcher {
            private $mailer;
            private $smtp;

            public function __construct(PHPMailer $mailer, DiagnosticSmtp $smtp)
            {
                $this->mailer = $mailer;
                $this->smtp = $smtp;
            }

            protected function createPhpMailer(): PHPMailer
            {
                return $this->mailer;
            }

            protected function createDiagnosticSmtp(): DiagnosticSmtp
            {
                return $this->smtp;
            }
        };

        $this->assertFalse($dispatcher->sendByCompany(0, 'email_verification', 'user@example.test', [
            'name' => 'Test',
            'link' => 'https://example.test/verify',
        ]));

        $log = EmailLog::where('recipient', 'user@example.test')
            ->where('template_key', 'email_verification')
            ->first();
        $this->assertSame(
            'SMTP Error: Could not authenticate. SMTP server reply: 535-5.7.8 Username and Password not accepted',
            $log->error_message
        );
        $this->assertStringNotContainsString('secret-app-password', $log->error_message);
    }

    public function testVerificationLinkActivatesAccountExactlyOnce()
    {
        $user = $this->makeUnverifiedUser();
        $token = EmailVerificationToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHour(),
        ]);

        $this->get('/verify-email/' . $token->token)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertNotNull($user->fresh()->email_verified_at, 'account should be verified');
        $this->assertNotNull($token->fresh()->used_at, 'token should be burned');

        $this->assertNotNull(
            EmailLog::where('recipient', $user->email)
                ->where('template_key', 'account_ready')
                ->where('status', 'sent')
                ->first(),
            'login-details mail should be logged after verification'
        );

        // One-time use: the same link cannot be replayed.
        $this->get('/verify-email/' . $token->token)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function testUnknownAndExpiredTokensAreRejected()
    {
        $this->get('/verify-email/' . Str::random(64))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $user = $this->makeUnverifiedUser();
        $expired = EmailVerificationToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->subMinute(),
        ]);

        $this->get('/verify-email/' . $expired->token)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertNull($user->fresh()->email_verified_at, 'expired link must not verify');
    }

    /**
     * Deploying the code before running database/email_system.sql must not
     * break signup or the verify link — both paths have to degrade quietly.
     */
    public function testFlowDegradesGracefullyWhenTablesAreMissing()
    {
        Schema::drop('email_verification_tokens');
        Schema::drop('email_templates');

        $response = $this->post('/register', $this->validRegistration());

        $response->assertRedirect(route('verification.notice'));
        $this->assertNotNull(User::where('email', 'token-flow@example.test')->first());

        $this->get('/verify-email/' . Str::random(64))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function testMailConfigPrefersCompanyRowThenDefaultRow()
    {
        MailConfig::create([
            'company_id' => null,
            'mail_name' => 'Default',
            'host' => 'smtp.default.test',
            'port' => 465,
            'username' => 'default@example.test',
            'password' => 'secret',
            'encryption' => 'ssl',
            'from_name' => 'CloudTech',
            'from_address' => 'no-reply@example.test',
            'is_active' => 1,
        ]);
        MailConfig::create([
            'company_id' => 7,
            'mail_name' => 'Company 7',
            'host' => 'smtp.company7.test',
            'port' => 587,
            'username' => 'c7@example.test',
            'password' => 'secret',
            'encryption' => 'tls',
            'from_name' => 'Company 7',
            'from_address' => 'c7@example.test',
            'is_active' => 1,
        ]);

        $this->assertSame('smtp.company7.test', MailConfig::active(7)->host);
        $this->assertSame('smtp.default.test', MailConfig::active(0)->host);
        $this->assertSame('smtp.default.test', MailConfig::active(9)->host);
    }

    private function validRegistration(): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'token-flow@example.test',
            'phone' => '01700000000',
            'address1' => '1 Test Street',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'postcode' => '1200',
            'currency' => 'BDT',
            'security_question' => 'What is your mother\'s maiden name?',
            'security_answer' => 'answer',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];
    }

    private function makeUnverifiedUser(): User
    {
        $user = new User([
            'name' => 'Token User',
            'email' => 'token-user@example.test',
            'password' => bcrypt('password123'),
        ]);
        $user->forceFill(['customer_uid' => User::generateUid()])->save();

        return $user;
    }

    private function createEmailTables(): void
    {
        Schema::create('mail_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('mail_name', 100)->nullable();
            $table->string('host');
            $table->integer('port');
            $table->string('username');
            $table->string('password');
            $table->string('encryption', 20)->default('tls');
            $table->string('from_name');
            $table->string('from_address');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key_name', 100)->unique();
            $table->string('subject');
            $table->mediumText('body');
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
    }
}
