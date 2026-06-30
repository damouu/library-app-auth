<?php

namespace App\Factory;

use App\DTO\JwtPayloadDTO;
use App\Models\User;

class JwtPayloadFactory
{
    public function __construct()
    {
    }

    public function fromUser(User $user): JwtPayloadDTO
    {
        return new JwtPayloadDTO(
            issuer: 'library-app-auth',
            audience: 'library-app-borrow',
            subject: (string)$user->getKey(),
            memberCardUuid: $user->card_uuid,
            avatarImgUrl: $user->avatar_img_url,
            username: $user->user_name,
            email: $user->email,
        );
    }
}
