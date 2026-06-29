<?php

namespace App\DTO;

class UserRegisteredEvent
{
    public function __construct(
        public readonly string $memberCardUuid,
        public readonly string $email,
        public readonly string $userName,
        public readonly string $createdAt,
    )
    {
    }

}
