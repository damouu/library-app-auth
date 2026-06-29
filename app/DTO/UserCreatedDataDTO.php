<?php

namespace App\DTO;

class UserCreatedDataDTO
{
    public function __construct(
        public readonly string $userName,
        public readonly string $email,
        public readonly string $avatarImgUrl,
        public readonly string $memberCardUuid,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'user_name' => $this->userName,
            'email' => $this->email,
            'avatar_img_url' => $this->avatarImgUrl,
            'member_card_uuid' => $this->memberCardUuid,
        ];
    }
}
