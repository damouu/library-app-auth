<?php

namespace App\DTO;

readonly class LoginRequestDTO
{
    public function __construct(
        public string $email,
        public string $password,
    )
    {
    }
}
