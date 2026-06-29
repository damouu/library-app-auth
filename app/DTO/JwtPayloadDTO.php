<?php

namespace App\DTO;

class JwtPayloadDTO
{
    public function __construct(
        public readonly string     $issuer,
        public readonly string     $audience,
        public readonly string|int $subject,
        public readonly string     $memberCardUuid,
        public readonly string     $avatarImgUrl,
        public readonly string     $username,
        public readonly string     $email,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $this->subject,
            'member_card_uuid' => $this->memberCardUuid,
            'avatar_img_url' => $this->avatarImgUrl,
            'user_name' => $this->username,
            'email' => $this->email,
        ];
    }
}
