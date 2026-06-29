<?php

namespace App\DTO;

readonly class EventMetadataDTO
{
    public function __construct(
        public string $timestamp,
        public string $sourceService,
        public string $eventType,
        public string $eventUuid,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'source_service' => $this->sourceService,
            'event_type' => $this->eventType,
            'event_uuid' => $this->eventUuid,
        ];
    }
}
