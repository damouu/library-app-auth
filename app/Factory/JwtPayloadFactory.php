<?php

namespace App\Factory;

use App\DTO\JwtPayloadDTO;
use App\Models\User;
use App\Services\TracingService;

class JwtPayloadFactory
{
    public function __construct(
        protected TracingService $tracingService,
    )
    {
    }

    public function fromUser(User $user): JwtPayloadDTO
    {
        return $this->tracingService->trace(
            'build-jwt-created-event',
            function () use ($user) {
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
        );
    }
}
