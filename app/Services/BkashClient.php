<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class BkashClient
{
    private $config;

    public function __construct()
    {
        $this->config = config('services.bkash');
    }

    public function isConfigured()
    {
        return $this->config['base_url'] && $this->config['app_key'] && $this->config['app_secret']
            && $this->config['username'] && $this->config['password'] && $this->config['callback_url'];
    }

    public function createPayment($amount, $invoice, $payerReference)
    {
        $grant = $this->grantToken();
        $token = is_array($grant) ? ($grant['id_token'] ?? null) : null;
        if (!$token) {
            // Propagate the token-grant error so callers can show bKash's real message.
            return is_array($grant) ? $grant : ['statusMessage' => 'Could not reach the bKash token endpoint.'];
        }

        return $this->request('/tokenized/checkout/create', [
            'mode' => '0011',
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'payerReference' => $payerReference,
            'merchantInvoiceNumber' => $invoice,
            'callbackURL' => $this->config['callback_url'],
        ], $token);
    }

    public function executePayment($paymentId)
    {
        return $this->request('/tokenized/checkout/execute', ['paymentID' => $paymentId], $this->token());
    }

    public function queryPayment($paymentId)
    {
        return $this->request('/tokenized/checkout/payment/status', ['paymentID' => $paymentId], $this->token());
    }

    private function token()
    {
        $grant = $this->grantToken();

        return is_array($grant) ? ($grant['id_token'] ?? null) : null;
    }

    /**
     * Request (or reuse the cached) id_token from bKash. Returns the raw response.
     */
    private function grantToken()
    {
        if (!$this->isConfigured()) {
            return ['statusMessage' => 'bKash is not configured on this server.'];
        }

        $cached = Cache::get('bkash.id_token');
        if ($cached) {
            return ['id_token' => $cached];
        }

        $response = $this->request('/tokenized/checkout/token/grant', [
            'app_key' => $this->config['app_key'],
            'app_secret' => $this->config['app_secret'],
        ], null, [
            // Header names/values must have NO space after the colon — matches the
            // dfninternet karim007/laravel-bkash-tokenize integration exactly.
            'username:' . $this->config['username'],
            'password:' . $this->config['password'],
        ]);

        if (is_array($response) && !empty($response['id_token'])) {
            Cache::put('bkash.id_token', $response['id_token'], 3300);
        }

        return $response ?: ['statusMessage' => 'No response from bKash token endpoint.'];
    }

    /**
     * The last status message returned by bKash (for surfacing gateway errors).
     */
    public function lastStatusMessage()
    {
        return Cache::get('bkash.last_status_message');
    }

    private function request($path, array $body, $token = null, array $headers = [])
    {
        // Mirrors karim007/laravel-bkash-tokenize exactly: no space after header colons,
        // no Accept header, FOLLOWLOCATION + IPRESOLVE_V4 enabled.
        $headers = array_merge(['Content-Type:application/json'], $headers);
        if ($token) {
            $headers[] = 'Authorization:' . $token;
            $headers[] = 'X-APP-Key:' . $this->config['app_key'];
        }

        $curl = curl_init(rtrim($this->config['base_url'], '/') . $path);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ]);
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false || $error) {
            \Log::warning('bKash request transport error', ['path' => $path, 'curl_error' => $error]);
            Cache::put('bkash.last_status_message', 'Gateway connection error: ' . $error, 300);

            return null;
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            \Log::warning('bKash request returned non-JSON', ['path' => $path, 'http_code' => $httpCode, 'body' => substr((string) $response, 0, 300)]);
            Cache::put('bkash.last_status_message', 'Unexpected gateway response (HTTP ' . $httpCode . ').', 300);

            return null;
        }

        if (isset($decoded['statusMessage']) && !isset($decoded['paymentID']) && !isset($decoded['id_token'])) {
            Cache::put('bkash.last_status_message', $decoded['statusMessage'], 300);
        }

        return $decoded;
    }
}