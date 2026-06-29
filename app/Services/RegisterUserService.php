<?php

namespace App\Services;

use App\DTO\RegisterResponseDTO;
use App\Factory\JwtPayloadFactory;
use App\Factory\UserCreatedEventFactory;
use App\Kafka\EventPublisher;
use Exception;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegisterUserService
{

    public function __construct(
        protected JWTService              $jwtService,
        protected TracingService          $tracingService,
        protected EventPublisher          $eventPublisher,
        protected UserCreatedEventFactory $userCreatedEventFactory,
        protected JwtPayloadFactory       $jwtPayloadFactory,
        protected UserRegistrationService $userRegistrationService
    )
    {
    }

    /**
     * @throws ValidationException|Exception|Throwable
     */
    public function register(array $validator): RegisterResponseDTO
    {
        return $this->tracingService->trace(
            'user-registration',
            function () use ($validator) {
                $user = $this->userRegistrationService->create($validator);
                $event = $this->userCreatedEventFactory->fromUser($user);
                $this->eventPublisher->publish($event);
                $payload = $this->jwtPayloadFactory->fromUser($user);
                $token = $this->jwtService->createToken($payload);
                return RegisterResponseDTO::fromToken(
                    token: $token,
                    expiresIn: 3600,
                );
            }
        );
    }
}
