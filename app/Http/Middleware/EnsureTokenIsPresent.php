<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsPresent
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');
        if (!$request->bearerToken() || $origin != 'http://localhost:3000/' || !$request->isMethod('POST') || !$request->accepts('application/json')) {
            return response()->json(['message' => false], 401);
        }
        return $next($request);
    }
}
