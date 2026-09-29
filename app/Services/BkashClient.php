<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Direct bKash Checkout (PGW popup) API client — v1.2.0-beta.
 *
 * Base URL (production): https://checkout.pay.bka.sh/v1.2.0-beta
 * (sandbox equivalent: https://checkout.sandbox.bka.sh/v1.2.0-beta)
 *
 * Endpoints used (from the merchant config table):
 *   POST /checkout/token/grant           headers: username, password | body: app_key, app_secret
 *   POST /checkout/payment/create        headers: authorization(id_token), x-app-key
 *   POST /checkout/payment/execute/{id}  headers: authorization(id_token), x-app-key
 *   POST /checkout/payment/query/{id}    headers: authorization(id_token), x-app-key
 *   POST /checkout/payment/search/{trx}  headers: authorization(id_token), x-app-key
 */
class BkashClient
{
    private $config;

    public function __construct()
    {
        $this->config = config('services.bkash');

        // Self-healing guard: the Checkout API lives on checkout.pay.bka.sh. If a
        // stale/cached config, an OS-level env var, or an Apache SetEnv still
        // points BKASH_BASE_URL at the old Tokenized host, requests would fail
        // with the API gateway's {"message":"Missing Authentication Token"}.
        // Force the correct host for this integration regardless of the source.
        $baseUrl = (string) ($this->config['base_url'] ?? '');
        if ($baseUrl === '' || stripos($baseUrl, 'tokenized.') !== false) {
            $this->config['base_url'] = 'https://checkout.pay.bka.sh/v1.2.0-beta';
        }
    }

    public function isConfigured()
    {
        return !empty($this->config['base_url'])
            && !empty($this->config['app_key'])
            && !empty($this->config['app_secret'])
            && !empty($this->config['username'])
            && !empty($this->config['password']);
    }

    /**
     * Grant (or reuse the cached) id_token. Returns the raw bKash response.
     */
    public function grantToken()
    {
        if (!$this->isConfigured()) {
            return ['statusMessage' => 'bKash is not configured on this server.'];
        }

        $cached = Cache::get('bkash.id_token');
        if ($cached) {
            return ['id_token' => $cached];
        }

        $grant = function () {
            return $this->request('/checkout/token/grant', [
                'app_key' => $this->config['app_key'],
                'app_secret' => $this->config['app_secret'],
            ], null, [
                // Header names/values must have NO space after the colon — matches
                // known-working bKash Checkout integrations on cURL.
                'username:' . $this->config['username'],
                'password:' . $this->config['password'],
            ]);
        };

        $response = $grant();

        // bKash intermittently rejects grants (e.g. "Invalid app key and secret
        // combination") right after several token requests in a row, even with
        // valid credentials. One short-backoff retry resolves the transient case.
        if (!is_array($response) || empty($response['id_token'])) {
            sleep(2);
            $response = $grant();
        }

        if (is_array($response) && !empty($response['id_token'])) {
            // id_token lasts ~1h; cache it slightly less to avoid expiry races.
            Cache::put('bkash.id_token', $response['id_token'], 3300);
            if (!empty($response['refresh_token'])) {
                Cache::put('bkash.refresh_token', $response['refresh_token'], 3300);
            }

            return $response;
        }

        // Authentication failed — produce an actionable message. The most common
        // cause is a BKASH_BASE_URL that does not match the Checkout API (e.g. the
        // old Tokenized host), which bKash's API gateway answers with
        // {"message":"Missing Authentication Token"}.
        // bKash uses several error shapes: statusMessage / errorMessage / message /
        // msg (the grant endpoint's {"status":"fail","msg":"..."} form).
        $message = (string) ($response['statusMessage'] ?? $response['errorMessage'] ?? $response['message'] ?? $response['msg'] ?? 'Unknown error');
        $baseUrl = (string) ($this->config['base_url'] ?? '');
        if (stripos($message, 'missing authentication token') !== false || stripos($baseUrl, 'tokenized.') !== false) {
            $message .= ' — BKASH_BASE_URL must be https://checkout.pay.bka.sh/v1.2.0-beta for the Checkout API (currently: '
                . ($baseUrl !== '' ? $baseUrl : 'not set') . ').';
        }
        Log::warning('bKash token grant failed', ['base_url' => $baseUrl, 'response' => $response]);
        Cache::put('bkash.last_status_message', $message, 300);

        return ['statusMessage' => $message];
    }

    /**
     * Create a checkout payment. Body mirrors the production ERP integration
     * exactly (amount/currency/merchantInvoiceNumber/intent — no payerReference).
     *
     * @return array bKash response (paymentID present on success)
     */
    public function createPayment($amount, $invoice, $payerReference = null)
    {
        $grant = $this->grantToken();
        $token = is_array($grant) ? ($grant['id_token'] ?? null) : null;
        if (!$token) {
            // Propagate the token-grant error so callers can show bKash's real message.
            return is_array($grant) ? $grant : ['statusMessage' => 'Could not reach the bKash token endpoint.'];
        }

        return $this->request('/checkout/payment/create', [
            'amount' => number_format((float) $amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => $invoice,
        ], $token);
    }

    /**
     * Execute (finalize) a payment the customer authorized in the popup.
     */
    public function executePayment($paymentId)
    {
        $token = $this->token();
        if (!$token) {
            // Fail fast with the real reason instead of calling bKash without
            // credentials (which only yields "Missing Authentication Token").
            return ['statusMessage' => $this->lastStatusMessage() ?: 'Could not authenticate with bKash.'];
        }

        return $this->request('/checkout/payment/execute/' . rawurlencode($paymentId), [], $token);
    }

    /**
     * Query a payment's current status (fallback when execute reports
     * "progressing" or the browser drops before the response arrives).
     */
    public function queryPayment($paymentId)
    {
        $token = $this->token();
        if (!$token) {
            return ['statusMessage' => $this->lastStatusMessage() ?: 'Could not authenticate with bKash.'];
        }

        return $this->request('/checkout/payment/query/' . rawurlencode($paymentId), [], $token);
    }

    /**
     * Search a transaction by transaction id (for reconciliation).
     */
    public function searchTransaction($trxId)
    {
        $token = $this->token();
        if (!$token) {
            return ['statusMessage' => $this->lastStatusMessage() ?: 'Could not authenticate with bKash.'];
        }

        return $this->request('/checkout/payment/search/' . rawurlencode($trxId), [], $token);
    }

    private function token()
    {
        $grant = $this->grantToken();

        return is_array($grant) ? ($grant['id_token'] ?? null) : null;
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
        // Mirrors the production ERP integration (Payment.php) byte-for-byte:
        // lowercase 'authorization' / 'x-app-key' with no space after the colon —
        // bKash's gateway treats these headers case-sensitively. Uppercase
        // 'X-APP-Key' gets errorCode 2001 "Invalid App Key" on
        // /checkout/payment/create even though the token grant succeeds.
        $headers = array_merge(['Content-Type:application/json'], $headers);
        if ($token) {
            $headers[] = 'authorization:' . $token;
            $headers[] = 'x-app-key:' . $this->config['app_key'];
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
            Log::warning('bKash request transport error', ['path' => $path, 'curl_error' => $error]);
            Cache::put('bkash.last_status_message', 'Gateway connection error: ' . $error, 300);

            return null;
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            Log::warning('bKash request returned non-JSON', ['path' => $path, 'http_code' => $httpCode, 'body' => substr((string) $response, 0, 300)]);
            Cache::put('bkash.last_status_message', 'Unexpected gateway response (HTTP ' . $httpCode . ').', 300);

            return null;
        }

        if (isset($decoded['statusMessage']) && !isset($decoded['paymentID']) && !isset($decoded['id_token'])) {
            Cache::put('bkash.last_status_message', $decoded['statusMessage'], 300);
        }

        return $decoded;
    }
}
