<?php

namespace App\DTO;

class UserCreatedEventDTO
{
    public function __construct(
        public readonly EventMetadataDTO   $metadata,
        public readonly UserCreatedDataDTO $data,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'metadata' => $this->metadata->toArray(),
            'data' => $this->data->toArray(),
        ];
    }
}
