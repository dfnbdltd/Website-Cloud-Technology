<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private $endpoint = 'https://hexarate.paikama.co/api/rates/latest/USD?target=BDT';

    public function usdToBdt()
    {
        $fallback = (float) config('services.exchange_rate.usd_to_bdt_fallback', 120);
        $cached = Cache::get('exchange_rate_usd_bdt');

        if (is_array($cached) && isset($cached['rate'], $cached['source'])) {
            return $cached;
        }

        $rate = $this->fetchLiveRate();
        if ($rate !== null) {
            $value = [
                'rate' => $rate,
                'source' => 'live',
                'updated_at' => now()->toIso8601String(),
            ];
            Cache::put('exchange_rate_usd_bdt', $value, 21600);

            return $value;
        }

        return [
            'rate' => $fallback,
            'source' => 'fallback',
            'updated_at' => null,
        ];
    }

    private function fetchLiveRate()
    {
        $handle = curl_init($this->endpoint);
        if ($handle === false) {
            Log::warning('Could not initialize USD to BDT exchange-rate request.');

            return null;
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($response === false || $status < 200 || $status >= 300) {
            Log::warning('USD to BDT exchange-rate request failed.', [
                'status' => $status,
                'error' => $error ?: 'HTTP request failed',
            ]);

            return null;
        }

        $payload = json_decode($response, true);
        $rate = isset($payload['data']['mid']) ? (float) $payload['data']['mid'] : null;

        if ($rate === null || $rate <= 0) {
            Log::warning('USD to BDT exchange-rate response was invalid.');

            return null;
        }

        return $rate;
    }
}
