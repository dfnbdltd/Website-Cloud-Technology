<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One-time verification token (guide section 3: email_verification_tokens).
 */
class EmailVerificationToken extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'token', 'expires_at', 'used_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
