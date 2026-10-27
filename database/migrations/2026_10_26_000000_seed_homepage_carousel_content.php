<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SeedHomepageCarouselContent extends Migration
{
    /**
     * Store the initial carousel copy in the CMS table without overwriting
     * values that may already have been customized on an existing site.
     */
    public function up()
    {
        $now = now();
        $values = [
            'hero_title' => 'Your Perfect Domain,',
            'hero_highlight' => 'Registered in Minutes.',
            'hero_text' => 'Search, register and manage your ideal domain name — real-time availability, transparent pricing in Taka, and zero hidden fees.',
            'ssl_title' => 'Secure your site.',
            'ssl_highlight' => 'Win instant trust.',
            'ssl_text' => 'A padlock in every browser, A+ grade encryption and a trust seal that turns hesitant visitors into customers — issued in minutes, installed free, and renewed automatically.',
            'communications_title' => 'Build your presence',
            'communications_highlight' => 'with confidence.',
            'communications_text' => 'Reliable web hosting and professional email service to keep your website fast, your brand credible, and your business connected.',
        ];

        foreach ($values as $key => $value) {
            if (!DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Cache::forget('site_settings');
    }

    public function down()
    {
        // Settings may have been customized after this migration ran, so do
        // not delete CMS content during a rollback.
    }
}
