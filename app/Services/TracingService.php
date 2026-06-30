<?php

namespace App\Services;

use Closure;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\StatusCode;

class TracingService
{
    public function trace(string $spanName, Closure $callback, array $attributes = []): mixed
    {
        $tracer = Globals::tracerProvider()->getTracer('auth-service');

        $span = $tracer
            ->spanBuilder($spanName)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        $scope = $span->activate();

        try {
            return $callback($span);
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR);

            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
