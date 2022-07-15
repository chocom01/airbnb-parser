<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $hash = hash_hmac('sha256', json_encode($request->all()), env('OMBIF_TOKEN'));

        return hash_equals($hash, $request->header('Token'))
            ? $next($request)
            : abort(403, 'Your token is not verified.');
    }
}
