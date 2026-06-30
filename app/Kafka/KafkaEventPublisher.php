<?php

namespace App\Kafka;

use App\dto\UserCreatedEventDTO;
use App\Services\TracingService;
use Junges\Kafka\Facades\Kafka;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use Throwable;

class KafkaEventPublisher implements EventPublisher
{
    public function __construct(
        protected TracingService $tracingService,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function publish(UserCreatedEventDTO $event): void
    {
        $this->tracingService->trace(
            'kafka.publish',
            function () use ($event) {
                $headers = [];

                TraceContextPropagator::getInstance()->inject($headers);

                Kafka::publish()
                    ->onTopic('auth-create-topic')
                    ->withHeaders($headers)
                    ->withKafkaKey($event->data->memberCardUuid)
                    ->withBody($event->toArray())
                    ->send();
            },
            [
                'event.uuid' => $event->metadata->eventUuid,
                'event.type' => $event->metadata->eventType,
                'event.source_service' => $event->metadata->sourceService,

                'messaging.system' => 'kafka',
                'messaging.destination.name' => 'auth-delete-topic',
                'messaging.operation' => 'publish',
                'messaging.message.id' => $event->data->memberCardUuid,
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function publishDelete(UserCreatedEventDTO $event): void
    {
        $this->tracingService->trace(
            'kafka.publish',
            function () use ($event) {
                $headers = [];

                TraceContextPropagator::getInstance()->inject($headers);

                Kafka::publish()
                    ->onTopic('auth-delete-topic')
                    ->withHeaders($headers)
                    ->withKafkaKey($event->data->memberCardUuid)
                    ->withBody($event->toArray())
                    ->send();
            },
            [
                'event.uuid' => $event->metadata->eventUuid,
                'event.type' => $event->metadata->eventType,
                'event.source_service' => $event->metadata->sourceService,

                'messaging.system' => 'kafka',
                'messaging.destination.name' => 'auth-delete-topic',
                'messaging.operation' => 'publish',
                'messaging.message.id' => $event->data->memberCardUuid,
            ]
        );
    }
}
