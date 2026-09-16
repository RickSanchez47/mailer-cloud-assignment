<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stands in for real authentication on the demo dashboard: redirects to
 * the account picker if no demo account has been selected in session yet.
 */
class EnsureAccountSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('account_id')) {
            return redirect()->route('builder.choose-account');
        }

        return $next($request);
    }
}
