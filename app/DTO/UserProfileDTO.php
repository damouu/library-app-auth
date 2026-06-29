<?php

namespace App\DTO;

use JsonSerializable;

readonly class UserProfileDTO implements JsonSerializable
{
    public function __construct(
        public string  $userName,
        public ?string $avatarUrl,
        public string  $email,
        public string  $cardUuid,
        public ?string $lastLoggedInAt,
    )
    {
    }

    public static function fromModel($user): self
    {
        return new self(
            userName: $user->user_name,
            avatarUrl: $user->avatar_img_url,
            email: $user->email,
            cardUuid: $user->card_uuid,
            lastLoggedInAt: $user->last_logged_in_at,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'user_name' => $this->userName,
            'avatar_img_url' => $this->avatarUrl,
            'email' => $this->email,
            'card_uuid' => $this->cardUuid,
            'last_logged_in_at' => $this->lastLoggedInAt,
        ];
    }
}
