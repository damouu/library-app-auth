<?php

namespace App\Services;

use App\DTO\UserProfileDTO;
use App\Repository\UserRepository;
use Throwable;

class GetUserProfile
{
    public function __construct(
        private JWTService     $jwtService,
        private TracingService $tracingService,
        private UserRepository $userRepository,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function getUserProfile(string $token): UserProfileDTO
    {
        return $this->tracingService->trace(
            'user.get_profile',
            function () use ($token) {
                $decoded = $this->jwtService->verifyToken($token);
                $user = $this->userRepository->findByEmail($decoded->email);
                return UserProfileDTO::fromModel($user);
            },
        );
    }

}
