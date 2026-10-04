-- ERP-style email system — see LARAVEL_EMAIL_IMPLEMENTATION_GUIDE.md
-- Run this file once against the target database (e.g. hostnova), then adjust rows as needed.
-- No real SMTP credentials are committed here: the config row is copied from mail_setups.

-- ----------------------------------------------------------------------------
-- 3. Recommended database tables
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS mail_configs (
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

CREATE TABLE IF NOT EXISTS email_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS email_logs (
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

CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL
);

-- ----------------------------------------------------------------------------
-- SMTP config for this tenant (company_id NULL = default row).
-- Prefer copying the active profile from the existing mail_setups table so no
-- password is ever typed into a file. Alternative manual insert is commented below.
-- ----------------------------------------------------------------------------

INSERT INTO mail_configs (company_id, mail_name, host, port, username, password, encryption, from_name, from_address, is_active)
SELECT NULL, 'Default (copied from mail_setups)', m_host, m_port, m_username, m_password,
       IF(smtp_secure IN ('ssl','tls'), smtp_secure, 'tls'), display_name, mail_address, 1
FROM mail_setups
WHERE status = 'Enable'
ORDER BY id
LIMIT 1;

-- Manual alternative (fill in your own values, never commit real passwords):
-- INSERT INTO mail_configs (company_id, mail_name, host, port, username, password, encryption, from_name, from_address, is_active)
-- VALUES (NULL, 'CloudTech default', 'smtp.example.com', 465, 'sender@example.com', 'your-app-password', 'ssl', 'CloudTech', 'no-reply@example.com', 1);

-- ----------------------------------------------------------------------------
-- 6. Email template records
-- ----------------------------------------------------------------------------

-- Guide example: verification mail (tokens: {{name}}, {{link}})
INSERT INTO email_templates (key_name, subject, body, is_active)
VALUES (
    'email_verification',
    'Verify your email, {{name}}',
    'Hello {{name}},\n\nPlease verify your email by clicking the link below:\n\n{{link}}\n\nRegards,\nYour App Team',
    1
);

-- Login-details mail sent after the address is verified
-- (tokens: {{name}}, {{email}}, {{customer_id}}, {{login_url}})
INSERT INTO email_templates (key_name, subject, body, is_active)
VALUES (
    'account_ready',
    'Your account is ready — login details',
    '<!doctype html><html><body style="margin:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:28px 12px"><tr><td align="center">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;padding:26px">
<tr><td>
<p style="margin:0 0 14px">Hi {{name}},</p>
<p style="margin:0 0 18px">Your email is verified and your CloudTech account is active. Here are your login details:</p>
<table cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;margin:0 0 20px">
<tr><td style="padding:10px 14px;color:#6b7280;width:130px">Login email</td><td style="padding:10px 14px;font-weight:bold">{{email}}</td></tr>
<tr><td style="padding:10px 14px;color:#6b7280;border-top:1px solid #eef0f3">Customer ID</td><td style="padding:10px 14px;font-weight:bold;border-top:1px solid #eef0f3">{{customer_id}}</td></tr>
<tr><td style="padding:10px 14px;color:#6b7280;border-top:1px solid #eef0f3">Password</td><td style="padding:10px 14px;border-top:1px solid #eef0f3">The password you chose when you signed up</td></tr>
</table>
<p style="margin:0 0 22px"><a href="{{login_url}}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:6px">Sign in to your account</a></p>
<p style="margin:0;color:#6b7280;font-size:12px">For your security we never send passwords by email. Keep your Customer ID handy when contacting support.</p>
</td></tr></table>
</td></tr></table>
</body></html>',
    1
);
