<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CatatAktivitas
{
    private const METHOD_DICATAT = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->user()
            && in_array($request->method(), self::METHOD_DICATAT, true)
            && ! in_array($request->route()?->getName(), ['login.masuk', 'logout'], true)
        ) {
            ActivityLog::catat($request, $response);
        }

        return $response;
    }
}
