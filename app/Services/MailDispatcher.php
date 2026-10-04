<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Services\DiagnosticSmtp;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Central mail service: SMTP settings come from Laravel config (.env),
 * message bodies come from email_templates, and every attempt is logged.
 */
class MailDispatcher
{
    public function sendPasswordReset(string $to, string $token): void
    {
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $to,
        ]);
        $body = view('emails.password-reset', [
            'resetUrl' => $resetUrl,
            'expiresIn' => config('auth.passwords.users.expire', 60),
        ])->render();

        $this->sendWithPhpMailer($to, 'Reset your CloudTech password', $body, true);
    }

    public function sendByCompany(int $companyId, string $templateKey, string $to, array $data = []): bool
    {
        $subject = null;

        try {
            $template = EmailTemplate::active($templateKey);

            if (!$template) {
                Log::error('Email template not found', ['template_key' => $templateKey]);
                $this->log($companyId, $to, $templateKey, null, 'failed', 'Template not found: ' . $templateKey);

                return false;
            }

            $subject = $this->replaceTokens($template->subject, $data);
            $body = $this->replaceTokens($template->body, $data);
            $isHtml = $this->isHtmlBody($body);

            if (config('mail.driver') === 'smtp') {
                $this->sendWithPhpMailer($to, $subject, $body, $isHtml);
            } else {
                $callback = function ($message) use ($to, $subject) {
                    $message->to($to)->subject($subject);
                };

                if ($isHtml) {
                    Mail::html($body, $callback);
                } else {
                    Mail::raw($body, $callback);
                }
            }

            $this->log($companyId, $to, $templateKey, $subject, 'sent', null);

            return true;
        } catch (\Throwable $e) {
            $this->log($companyId, $to, $templateKey, $subject, 'failed', $e->getMessage());
            Log::error('Mail sending failed', ['template_key' => $templateKey, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function replaceTokens(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string) $value, $content);
        }

        return $content;
    }

    private function isHtmlBody(string $body): bool
    {
        return preg_match('/<[a-z][^>]*>/i', $body) === 1;
    }

    private function sendWithPhpMailer(string $to, string $subject, string $body, bool $isHtml): void
    {
        $mailer = $this->createPhpMailer();
        $smtp = $this->createDiagnosticSmtp();
        $encryption = strtolower((string) config('mail.encryption'));

        $mailer->isSMTP();
        $mailer->setSMTPInstance($smtp);
        $mailer->Host = config('mail.host');
        $mailer->Port = (int) config('mail.port');
        $mailer->SMTPAuth = (string) config('mail.username') !== '';
        $mailer->Username = (string) config('mail.username');
        $mailer->Password = preg_replace('/\s+/', '', (string) config('mail.password'));
        $mailer->SMTPSecure = $encryption === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : ($encryption === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : '');
        $mailer->SMTPAutoTLS = $encryption === 'tls';
        $mailer->Timeout = 30;
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;
        $mailer->setFrom(
            (string) config('mail.from.address'),
            (string) config('mail.from.name')
        );
        $mailer->addAddress($to);
        $mailer->Subject = $subject;
        $mailer->isHTML($isHtml);
        $mailer->Body = $body;
        if ($isHtml) {
            $mailer->AltBody = trim(html_entity_decode(
                preg_replace('/[ \t]*\n[ \t]*/', "\n", $mailer->html2text($body)),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            ));
        }

        try {
            if (!$mailer->send()) {
                throw new \RuntimeException($mailer->ErrorInfo ?: 'PHPMailer could not send the message.');
            }
        } catch (\Throwable $e) {
            $failure = $smtp->getAuthenticationFailure();
            $reply = $failure ? preg_replace('/[\r\n]+/', ' ', $failure['reply']) : '';
            $sensitiveValues = array_filter([
                $mailer->Username,
                $mailer->Password,
                base64_encode($mailer->Username),
                base64_encode($mailer->Password),
            ], function ($value) {
                return $value !== '';
            });
            $reply = str_replace($sensitiveValues, '[redacted]', $reply);

            if (!$reply || !preg_match('/^[245]\d{2}(?:[- ].*)?$/', $reply)) {
                throw $e;
            }

            throw new \RuntimeException($e->getMessage() . ' SMTP server reply: ' . $reply, 0, $e);
        }
    }

    protected function createPhpMailer(): PHPMailer
    {
        return new PHPMailer(true);
    }

    protected function createDiagnosticSmtp(): DiagnosticSmtp
    {
        return new DiagnosticSmtp();
    }

    /**
     * Never let logging break a send (table may not exist yet).
     */
    private function log(int $companyId, string $to, ?string $templateKey, ?string $subject, string $status, ?string $error): void
    {
        try {
            if (!Schema::hasTable('email_logs')) {
                return;
            }

            EmailLog::create([
                'company_id' => $companyId ?: null,
                'recipient' => $to,
                'template_key' => $templateKey,
                'subject' => $subject,
                'status' => $status,
                'error_message' => $error,
                'sent_at' => $status === 'sent' ? now() : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Could not write email_logs', ['error' => $e->getMessage()]);
        }
    }
}
