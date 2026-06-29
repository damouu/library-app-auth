<?php

namespace App\Services;

use App\Contracts\PasswordVerifier;
use App\DTO\UserProfileDTO;
use App\Factory\JwtPayloadFactory;
use App\Factory\UserCreatedEventFactory;
use App\Kafka\EventPublisher;
use App\Repository\UserRepository;
use Throwable;

class GetUserProfile
{
    public function __construct(
        protected JWTService              $jwtService,
        protected TracingService          $tracingService,
        protected EventPublisher          $eventPublisher,
        protected UserCreatedEventFactory $userCreatedEventFactory,
        protected JwtPayloadFactory       $jwtPayloadFactory,
        protected UserRegistrationService $userRegistrationService,
        protected UserRepository          $userRepository,
        protected PasswordVerifier        $passwordVerifier,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function getUserProfile(string $token): UserProfileDTO
    {
        return $this->tracingService->trace(
            'user-get-profile',
            function () use ($token) {
                $decoded = $this->jwtService->verifyToken($token);
                $user = $this->userRepository->findByEmail($decoded->email);
                return UserProfileDTO::fromModel($user);
            });
    }

}
