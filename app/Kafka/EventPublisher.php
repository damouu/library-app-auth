<?php

namespace App\Kafka;

use App\dto\UserCreatedEventDTO;

interface EventPublisher
{
    public function publish(UserCreatedEventDTO $event): void;

    public function publishDelete(UserCreatedEventDTO $event): void;

}
