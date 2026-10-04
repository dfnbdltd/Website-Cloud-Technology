<?php

namespace App;

use App\Services\MailDispatcher;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public function sendPasswordResetNotification($token)
    {
        app(MailDispatcher::class)->sendPasswordReset($this->email, $token);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
        'first_name', 'last_name', 'phone', 'company_name', 'address1', 'address2',
        'city', 'country', 'state', 'postcode', 'currency', 'found_us',
        'security_question', 'security_answer', 'newsletter',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'newsletter' => 'boolean',
        'balance' => 'float',
    ];

    /**
     * Generate the next sequential customer unique ID (CT-000001, CT-000002, ...).
     * Deliberately DB-agnostic (works on MySQL and SQLite) by computing the
     * numeric suffix in PHP.
     */
    public static function generateUid()
    {
        $prefix = config('services.customer.uid_prefix', 'CT');
        $max = 0;

        foreach (static::query()->whereNotNull('customer_uid')->pluck('customer_uid') as $uid) {
            if (strpos((string) $uid, $prefix . '-') === 0) {
                $max = max($max, (int) substr((string) $uid, strlen($prefix) + 1));
            }
        }

        $next = $max + 1;

        do {
            $uid = $prefix . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (static::where('customer_uid', $uid)->exists());

        return $uid;
    }

    /**
     * Two-letter avatar initials: "M Hafuz" -> "MH", "Rahim" -> "RA".
     */
    public static function initials($name)
    {
        $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return 'C';
        }
        $initials = count($words) > 1
            ? mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1)
            : mb_substr($words[0], 0, 2);

        return mb_strtoupper($initials);
    }

    /**
     * Conversations channel key used by the live chat (works for guests too).
     */
    public function chatSessionId()
    {
        return 'user-' . $this->id;
    }

    public function topups()
    {
        return $this->hasMany('App\Models\Topup');
    }

    public function chatMessages()
    {
        return $this->hasMany('App\Models\ChatMessage');
    }
}
