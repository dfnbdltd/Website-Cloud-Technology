<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * SMTP profile (same layout as the ERP's dfn_mail_setup table).
 */
class MailSetup extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $hidden = ['m_password'];

    public static function active()
    {
        // Fall back to .env mail settings until the migration has run.
        if (!Schema::hasTable('mail_setups')) {
            return null;
        }

        return static::where('status', 'Enable')->orderBy('id')->first();
    }

    /**
     * Point Laravel's mailer at this profile.
     */
    public function apply()
    {
        $secure = strtolower(trim($this->smtp_secure));

        config([
            'mail.driver' => 'smtp',
            'mail.host' => trim($this->m_host),
            'mail.port' => (int) $this->m_port,
            'mail.encryption' => in_array($secure, ['ssl', 'tls']) ? $secure : null,
            'mail.username' => $this->smtp_auth === '1' ? trim($this->m_username) : null,
            // Gmail app passwords are often stored with stray spaces.
            'mail.password' => $this->smtp_auth === '1' ? str_replace(' ', '', $this->m_password) : null,
            'mail.from.address' => trim($this->mail_address),
            'mail.from.name' => trim($this->display_name),
        ]);
    }
}
