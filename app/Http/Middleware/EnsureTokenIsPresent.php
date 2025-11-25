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
        $authHeader = $request->header('Authorization');

        if (empty($authHeader)) {
            return response()->json(['message' => 'Missing Authorization header'], 401);
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Invalid Authorization header format'], 401);
        }

        if (!$request->acceptsJson()) {
            return response()->json(['message' => 'Client must accept JSON'], 406);
        }
        
        return $next($request);
    }
}
