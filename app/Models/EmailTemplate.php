<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * DB-stored mail template (guide section 3: email_templates).
 * Subject/body contain {{token}} placeholders replaced at send time.
 */
class EmailTemplate extends Model
{
    protected $fillable = ['key_name', 'subject', 'body', 'is_active'];

    public static function active(string $keyName): ?self
    {
        if (!Schema::hasTable('email_templates')) {
            return null;
        }

        return static::where('key_name', $keyName)->where('is_active', 1)->first();
    }
}
