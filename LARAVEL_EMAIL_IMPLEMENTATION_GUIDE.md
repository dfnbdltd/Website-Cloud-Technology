# Laravel Email & Verification Implementation Guide

This document explains how to implement the ERP-style email system in another Laravel project in a clean and production-safe way.

## 1. Goal

The ERP pattern is:

- SMTP config is stored per company or tenant
- email template is selected dynamically
- sending is centralized in one service
- status is logged for every email
- verification email is sent with secure token-based flow

This guide keeps the same logic, but removes any real credentials and uses safe placeholders.

---

## 2. SMTP configuration in Laravel

Put credentials in `.env`:

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-sender@example.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="Your App"
```

This project sends SMTP mail through PHPMailer (`phpmailer/phpmailer`), configured from these Laravel mail settings.

For Gmail, use `smtp.gmail.com`, port `587`, and `tls`. `MAIL_PASSWORD` must be a Google App Password (requires 2-Step Verification), not the account password. The `MAIL_FROM_ADDRESS` must match the authenticated account or be configured and verified as one of its "Send mail as" aliases. After changing `.env` on a server, clear Laravel's cached configuration with `php artisan config:clear` (and restart any queue workers).

For multi-company systems, keep SMTP values in a database table instead of a global config only.

---

## 3. Recommended database tables

### mail_configs

```sql
CREATE TABLE mail_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NULL,
    mail_name VARCHAR(100) NULL,
    host VARCHAR(255) NOT NULL,
    port INT NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    encryption VARCHAR(20) DEFAULT 'tls',
    from_name VARCHAR(255) NOT NULL,
    from_address VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### email_templates

```sql
CREATE TABLE email_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### email_logs

```sql
CREATE TABLE email_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    recipient VARCHAR(255) NOT NULL,
    template_key VARCHAR(100) NULL,
    subject VARCHAR(255) NULL,
    status ENUM('queued','sent','failed') DEFAULT 'queued',
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL
);
```

### email_verification_tokens

```sql
CREATE TABLE email_verification_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL
);
```

---

## 4. Central mail service

This is the standard ERP-style pattern: one class handles all email sending.

```php
namespace App\Services;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\MailConfig;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailDispatcher
{
    public function sendByCompany(int $companyId, string $templateKey, string $to, array $data = []): bool
    {
        $config = MailConfig::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        if (!$config) {
            Log::error('No SMTP config found for company', ['company_id' => $companyId]);
            return false;
        }

        config([
            'mail.mailer' => 'smtp',
            'mail.host' => $config->host,
            'mail.port' => $config->port,
            'mail.username' => $config->username,
            'mail.password' => $config->password,
            'mail.encryption' => $config->encryption,
            'mail.from.address' => $config->from_address,
            'mail.from.name' => $config->from_name,
        ]);

        $template = EmailTemplate::where('key_name', $templateKey)
            ->where('is_active', 1)
            ->first();

        if (!$template) {
            Log::error('Email template not found', ['template_key' => $templateKey]);
            return false;
        }

        $subject = $this->replaceTokens($template->subject, $data);
        $body = $this->replaceTokens($template->body, $data);

        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject);
            });

            EmailLog::create([
                'company_id' => $companyId,
                'recipient' => $to,
                'template_key' => $templateKey,
                'subject' => $subject,
                'status' => 'sent',
            ]);

            return true;
        } catch (\Exception $e) {
            EmailLog::create([
                'company_id' => $companyId,
                'recipient' => $to,
                'template_key' => $templateKey,
                'subject' => $subject,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Mail sending failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function replaceTokens(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }
}
```

---

## 5. Email verification flow

### Register user

```php
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
    'email_verified_at' => null,
]);
```

### Generate token

```php
use Illuminate\Support\Str;

$token = Str::random(64);

EmailVerificationToken::create([
    'user_id' => $user->id,
    'token' => $token,
    'expires_at' => now()->addHour(),
]);
```

### Send verification email

```php
app(MailDispatcher::class)->sendByCompany(
    $user->company_id,
    'email_verification',
    $user->email,
    [
        'name' => $user->name,
        'link' => url('/verify-email/' . $token),
    ]
);
```

### Verify link

```php
public function verifyEmail($token)
{
    $record = EmailVerificationToken::where('token', $token)
        ->whereNull('used_at')
        ->first();

    if (!$record || $record->expires_at->isPast()) {
        return redirect('/login')->with('error', 'Verification link is invalid or expired.');
    }

    $user = User::find($record->user_id);
    $user->email_verified_at = now();
    $user->save();

    $record->used_at = now();
    $record->save();

    return redirect('/login')->with('success', 'Email verified successfully.');
}
```

---

## 6. Example email template record

```sql
INSERT INTO email_templates (key_name, subject, body, is_active)
VALUES (
    'email_verification',
    'Verify your email, {{name}}',
    'Hello {{name}},\n\nPlease verify your email by clicking the link below:\n\n{{link}}\n\nRegards,\nYour App Team',
    1
);
```

---

## 7. Queue-based sending

For production, use Laravel queue.

Create a job:

```bash
php artisan make:job SendEmailJob
```

```php
namespace App\Jobs;

use App\Services\MailDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $companyId;
    public $templateKey;
    public $to;
    public $data;

    public function __construct($companyId, $templateKey, $to, $data)
    {
        $this->companyId = $companyId;
        $this->templateKey = $templateKey;
        $this->to = $to;
        $this->data = $data;
    }

    public function handle()
    {
        app(MailDispatcher::class)->sendByCompany(
            $this->companyId,
            $this->templateKey,
            $this->to,
            $this->data
        );
    }
}
```

Then dispatch:

```php
SendEmailJob::dispatch($user->company_id, 'email_verification', $user->email, [
    'name' => $user->name,
    'link' => url('/verify-email/' . $token),
]);
```

---

## 8. Best practices

- Never commit actual SMTP passwords
- Use `.env` or secret manager
- Use a dedicated sender email account
- Add expiry time for verification tokens
- Store only minimal token data
- Use queues for mail sending
- Log failures with details, but never include secrets in logs
- Validate email addresses before sending
- Keep a separate mail config per tenant/company

---

## 9. Very short final flow

1. User registers
2. Generate verification token
3. Pick SMTP config based on company
4. Select email template by key
5. Replace placeholders with values
6. Send email through queue
7. Log success/failure
8. User clicks link and verifies email

---

## 10. Summary

The ERP logic can be copied safely into Laravel by using:

- company-based SMTP config
- template-driven mail sending
- centralized service
- queue-based dispatch
- secure token verification

This is the correct and production-friendly pattern for building an email verification system in another project.
