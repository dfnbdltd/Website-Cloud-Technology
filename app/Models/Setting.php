<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value site content edited from the admin CMS (admin/content).
 */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    /**
     * Editable site content: key => [label, default, type]. The admin CMS form
     * is generated from this list and every public view reads through get().
     */
    public static function fields()
    {
        return [
            'Contact' => [
                'support_phone' => ['Support phone', '+880 9613 003 001', 'text'],
                'support_email' => ['Support email', 'support@cloudtechnologybd.net', 'text'],
                'manual_payment_number' => ['bKash / Nagad number for manual payments', '01713-401914', 'text'],
            ],
            'Homepage hero' => [
                'hero_title' => ['Headline', 'Your Perfect Domain,', 'text'],
                'hero_highlight' => ['Headline highlight', 'Registered in Minutes.', 'text'],
                'hero_text' => ['Intro paragraph', 'Search, register and manage your ideal domain name — real-time availability, transparent pricing in Taka, and zero hidden fees.', 'textarea'],
            ],
            'SSL slider' => [
                'ssl_title' => ['SSL headline', 'Secure your site.', 'text'],
                'ssl_highlight' => ['SSL headline highlight', 'Build instant trust.', 'text'],
                'ssl_text' => ['SSL paragraph', '256-bit encryption, the browser padlock and a trust seal on every visit — issued in minutes with free installation and automated renewal.', 'textarea'],
            ],
            'Homepage trust' => [
                'trust_items' => ['Trust bar (comma separated)', 'Real-time availability, Live BDT pricing, Free WHOIS privacy, 24/7 local support', 'text'],
            ],
            'Pricing & call to action' => [
                'pricing_title' => ['Pricing heading', 'Transparent pricing, updated live.', 'text'],
                'pricing_text' => ['Pricing text', 'Prices come directly from our registrar in Bangladeshi Taka — what you see is exactly what you pay.', 'textarea'],
                'cta_title' => ['Call-to-action heading', 'Found your domain?', 'text'],
                'cta_text' => ['Call-to-action text', 'Search it, add it to the cart and check out — you only sign in when it is time to pay.', 'textarea'],
                'footer_text' => ['Footer text', 'CloudTech. All rights reserved.', 'text'],
            ],
        ];
    }

    public static function get($key, $default = null)
    {
        $all = static::allValues();
        if (isset($all[$key]) && $all[$key] !== '') {
            return $all[$key];
        }
        if ($default !== null) {
            return $default;
        }
        foreach (static::fields() as $group) {
            if (isset($group[$key])) {
                return $group[$key][1];
            }
        }

        return null;
    }

    public static function allValues()
    {
        // Keep public pages working before the settings migration has run.
        if (!Cache::has('site_settings') && !Schema::hasTable('settings')) {
            return [];
        }

        return Cache::rememberForever('site_settings', function () {
            return static::query()->pluck('value', 'key')->all();
        });
    }

    public static function put(array $values)
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('site_settings');
    }
}
