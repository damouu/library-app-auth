<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBasicIsPresent
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authRequest = $request->header('Authorization');
        $origin = $request->header('Origin');
        $basicIsPresent = substr($authRequest, 0, 5);
        if ($origin != 'http://localhost:3000' || !$basicIsPresent || !$request->header('Authorization') || !$request->isMethod('POST') || !$request->accepts('application/json')) {
            return response()->json(['message' => false], 401);
        }
        return $next($request);
    }
}
