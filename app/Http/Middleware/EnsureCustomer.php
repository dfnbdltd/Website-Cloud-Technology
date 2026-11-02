<?php

namespace App\Http\Middleware;

use App\Support\AuthIntent;
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

        // A GET is simply resumed after signing in. A POST can't be replayed, so
        // send the customer back to the page that hosted the form — never to the
        // POST target itself, which would answer 405 when opened as a GET.
        if ($request->isMethod('GET')) {
            $returnTo = $request->getRequestUri();
        } elseif ($request->routeIs('domains.search') && $request->filled('domain')) {
            $returnTo = route('domains.index', ['domain' => $request->input('domain')]);
        } else {
            $returnTo = $request->headers->get('referer') ?: route('dashboard');
        }

        AuthIntent::remember($request->session(), $returnTo);

        return redirect()
            ->route('login', ['return_to' => $returnTo])
            ->with('success', 'Please sign in or create an account to continue.');
    }
}
