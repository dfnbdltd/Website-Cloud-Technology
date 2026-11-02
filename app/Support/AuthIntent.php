<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;

/**
 * Where a guest was headed before we asked them to sign in.
 *
 * Checkout links to /login?return_to=/domains/checkout and the client-area
 * guard does the same, so a buyer resumes the order they were placing instead
 * of being dropped on the dashboard. A login opened without a destination (the
 * header "Log in" button) clears any older value first, so a plain sign-in
 * still starts in the dashboard.
 *
 * Only same-site paths are ever stored, which keeps the value from being used
 * as an open redirect.
 */
class AuthIntent
{
    /**
     * Laravel's own key, so redirect()->guest()/intended() stay compatible.
     */
    const SESSION_KEY = 'url.intended';

    /**
     * When the destination was captured, so a stale one can be ignored.
     */
    const SESSION_STAMP_KEY = 'url.intended_at';

    /**
     * A destination older than this is ignored — long enough to cover
     * "register, open the verification mail, come back later".
     */
    const TTL = 86400; // 24 hours

    /**
     * Store a same-site destination for the next successful sign-in.
     */
    public static function remember(Session $session, $url): void
    {
        $path = self::sanitize($url);
        if ($path === null) {
            return;
        }

        $session->put(self::SESSION_KEY, $path);
        $session->put(self::SESSION_STAMP_KEY, time());
    }

    /**
     * Drop any stored destination.
     */
    public static function forget(Session $session): void
    {
        $session->forget(self::SESSION_KEY);
        $session->forget(self::SESSION_STAMP_KEY);
    }

    /**
     * Remember a real destination, or clear a stale one when the visitor
     * opened the sign-in screen on its own (no return_to).
     */
    public static function replace(Session $session, $url): void
    {
        if (self::sanitize($url) === null) {
            self::forget($session);

            return;
        }

        self::remember($session, $url);
    }

    /**
     * The stored destination without consuming it (used to forward the value
     * through intermediate screens such as login -> register).
     *
     * @return string|null
     */
    public static function peek(Session $session)
    {
        $path = $session->get(self::SESSION_KEY);
        $stamp = (int) $session->get(self::SESSION_STAMP_KEY, time());

        if (!is_string($path) || self::sanitize($path) === null || (time() - $stamp) > self::TTL) {
            return null;
        }

        return $path;
    }

    /**
     * Read and consume the destination. Returns $fallback when nothing usable
     * was stored, so a plain sign-in lands on the dashboard.
     *
     * @param  string  $fallback
     * @return string
     */
    public static function pull(Session $session, $fallback)
    {
        $path = $session->pull(self::SESSION_KEY);
        $stamp = (int) $session->pull(self::SESSION_STAMP_KEY, time());

        if (!is_string($path) || self::sanitize($path) === null || (time() - $stamp) > self::TTL) {
            return $fallback;
        }

        return $path;
    }

    /**
     * Reduce a URL to a local path, or null when it points anywhere else.
     *
     * @param  mixed  $url
     * @return string|null
     */
    public static function sanitize($url)
    {
        if (!is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || $url === '#') {
            return null;
        }

        // No control characters: they would end up in a Location header.
        if (preg_match('/[\x00-\x1f\x7f]/', $url)) {
            return null;
        }

        if ($url[0] === '/') {
            // "/x" is local; "//host" and "/\host" are protocol-relative.
            $second = isset($url[1]) ? $url[1] : '';

            return in_array($second, ['/', '\\'], true) ? null : $url;
        }

        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host']) || empty($parts['path'])) {
            return null;
        }
        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }
        if (strtolower($parts['host']) !== strtolower(request()->getHost())) {
            return null;
        }

        // Same site: keep only the local part so the stored value is a path.
        $path = $parts['path'];
        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?' . $parts['query'];
        }
        if (isset($parts['fragment']) && $parts['fragment'] !== '') {
            $path .= '#' . $parts['fragment'];
        }

        return self::sanitize($path);
    }
}
