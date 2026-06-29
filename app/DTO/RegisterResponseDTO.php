<?php

namespace App\DTO;

class RegisterResponseDTO
{
    public function __construct(
        public string $token,
        public int    $expiresIn,
        public string $expiresAt,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $this->token,
            'expires_in' => $this->expiresIn,
            'expires_at' => $this->expiresAt,
        ];
    }

    public static function fromToken(
        string $token,
        int    $expiresIn
    ): self
    {
        return new self(
            token: $token,
            expiresIn: $expiresIn,
            expiresAt: date('c', time() + $expiresIn),
        );
    }
}
