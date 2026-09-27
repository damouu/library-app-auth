<?php

namespace App\Factory;

use App\DTO\JwtPayloadDTO;
use App\Models\User;


class JwtPayloadFactory
{

    public function __construct()
    {
    }

    /**
     * @param User $user
     * @return JwtPayloadDTO
     */
    public function fromUser(User $user): JwtPayloadDTO
    {
        return new JwtPayloadDTO(
            issuer: 'library-app-auth',
            audience: 'library-app-borrow',
            subject: (string)$user->getKey(),
            memberCardUuid: $user->card_uuid
        );
    }
}
