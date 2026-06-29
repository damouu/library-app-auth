<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenTelemetry\API\Globals;

class OpenTelemetryMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tracer = Globals::tracerProvider()->getTracer('laravel-http');
        $span = $tracer
            ->spanBuilder($request->method() . ' ' . $request->path())
            ->startSpan();

        $scope = $span->activate();

        try {
            return $next($request);
        } catch (\Throwable $e) {
            $span->recordException($e);
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
