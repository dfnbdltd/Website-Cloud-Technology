<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Per-company SMTP profile (guide section 3: mail_configs).
 * company_id NULL is the default row used by this single-tenant app.
 */
class MailConfig extends Model
{
    protected $fillable = [
        'company_id', 'mail_name', 'host', 'port', 'username', 'password',
        'encryption', 'from_name', 'from_address', 'is_active',
    ];

    /**
     * Active profile for a company, falling back to the default (NULL) row.
     */
    public static function active(int $companyId): ?self
    {
        // Keep mail working before database/email_system.sql has been run.
        if (!Schema::hasTable('mail_configs')) {
            return null;
        }

        return static::where('company_id', $companyId)->where('is_active', 1)->first()
            ?: static::whereNull('company_id')->where('is_active', 1)->first();
    }
}
