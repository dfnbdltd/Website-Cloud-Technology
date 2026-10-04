<?php

namespace App\Http\Middleware;

use Closure;

class EnsureCustomer
{
    /**
     * Keep guests out of the client area. Customers sign in through
     * AuthController, which stores them in session('customer').
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user() || $request->session()->get('customer')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Please sign in to continue.'], 401);
        }

        // A POST can't be replayed after login, so send the customer back to a page instead.
        if (!$request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->routeIs('domains.search') && $request->filled('domain')
                ? route('domains.index', ['domain' => $request->input('domain')])
                : url()->previous());

            return redirect()->route('login')->with('success', 'Please sign in or create an account to continue.');
        }

        return redirect()->guest(route('login'))->with('success', 'Please sign in or create an account to continue.');
    }
}
