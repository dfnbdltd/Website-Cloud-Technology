<?php

namespace App\Support;

/**
 * "Where the customer came from" for pages that are opened from several lists.
 *
 * An invoice is reachable from My Store → Invoices, My Store → Payments, a
 * domain card, the Manage page and a service page. Its Back button used to be
 * hardcoded to the invoices list, which dropped anyone who came from anywhere
 * else. Links now carry ?back=<current path> and the target renders Back with
 * that path, so leaving a page returns to the list the customer was on.
 *
 * Only same-site paths are ever accepted (AuthIntent::sanitize), so a crafted
 * ?back= can never turn the button into an open redirect.
 */
class ReturnTo
{
    const KEY = 'back';

    /**
     * The current path + query, with any previous "back" removed so repeated
     * hops never nest: /mystore/payments -> invoice keeps no chain.
     *
     * @return string|null
     */
    public static function path()
    {
        $uri = request()->getRequestUri();
        $pieces = explode('?', (string) $uri, 2);
        $path = $pieces[0];
        $query = isset($pieces[1]) ? $pieces[1] : '';

        if ($query !== '') {
            parse_str($query, $params);
            unset($params[self::KEY]);
            $query = http_build_query($params);
        }

        return self::sanitize($query === '' ? $path : $path . '?' . $query);
    }

    /**
     * The Back destination for a page: the sanitized ?back= value, else the
     * fallback URL the caller passed (normally the section's own index).
     *
     * @param  string  $fallback
     * @return string
     */
    public static function url($fallback)
    {
        $back = self::sanitize(request()->query(self::KEY));

        return $back === null ? $fallback : $back;
    }

    /**
     * Reduce a value to a local path, or null when it points anywhere else.
     *
     * @param  mixed  $url
     * @return string|null
     */
    public static function sanitize($url)
    {
        return AuthIntent::sanitize($url);
    }
}
