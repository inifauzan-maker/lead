<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.integration.token');
        $incoming = $request->bearerToken() ?: $request->header('X-Api-Token');

        abort_if(blank($expected) || ! hash_equals((string) $expected, (string) $incoming), 401);

        return $next($request);
    }
}
