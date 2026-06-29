<?php

namespace App\Kafka;

use App\dto\UserCreatedEventDTO;
use App\Services\TracingService;
use Junges\Kafka\Facades\Kafka;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;

class KafkaEventPublisher implements EventPublisher
{
    public function __construct(
        protected TracingService $tracingService,
    )
    {
    }

    /**
     * @throws \Throwable
     */
    public function publish(UserCreatedEventDTO $event): void
    {
        $this->tracingService->trace(
            'kafka-publish-event',
            function () use ($event) {
                $headers = [];
                TraceContextPropagator::getInstance()->inject($headers);
                Kafka::publish()
                    ->onTopic('auth-create-topic')
                    ->withHeaders($headers)
                    ->withKafkaKey($event->data->memberCardUuid)
                    ->withBody($event->toArray())
                    ->send();
            }
        );
    }

    public function publishDelete(UserCreatedEventDTO $event): void
    {
        $this->tracingService->trace(
            'kafka-publish-event',
            function () use ($event) {
                $headers = [];
                TraceContextPropagator::getInstance()->inject($headers);
                Kafka::publish()
                    ->onTopic('auth-delete-topic')
                    ->withHeaders($headers)
                    ->withKafkaKey($event->data->memberCardUuid)
                    ->withBody($event->toArray())
                    ->send();
            }
        );
    }
}
