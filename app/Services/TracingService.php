<?php

namespace App\Services;

use Closure;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use Throwable;

class TracingService
{
    /**
     * @param string $spanName
     * @param Closure $callback
     * @param array $attributes
     * @return mixed
     * @throws Throwable
     */
    public function trace(string $spanName, Closure $callback, array $attributes = []): mixed
    {
        $tracer = Globals::tracerProvider()->getTracer('auth-service');

        $span = $tracer
            ->spanBuilder($spanName)
            ->startSpan();

        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        $context = $span->storeInContext(Context::getCurrent());

        $scope = $context->activate();

        try {
            return $callback($span);
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR);
            throw $e;
        } finally {
            @$scope->detach();
            $span->end();
        }
    }
}
