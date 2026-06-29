<?php

namespace App\Services;

use App\Contracts\PasswordVerifier;
use App\Factory\JwtPayloadFactory;
use App\Factory\UserCreatedEventFactory;
use App\Factory\UserDeletedEventFactory;
use App\Kafka\EventPublisher;
use App\Repository\UserRepository;
use OpenTelemetry\API\Trace\SpanInterface;

class AuthService
{
    public function __construct(
        protected JWTService              $jwtService,
        protected TracingService          $tracingService,
        protected EventPublisher          $eventPublisher,
        protected UserCreatedEventFactory $userCreatedEventFactory,
        protected UserDeletedEventFactory $userDeletedEventFactory,
        protected JwtPayloadFactory       $jwtPayloadFactory,
        protected UserRegistrationService $userRegistrationService,
        protected UserRepository          $userRepository,
        protected PasswordVerifier        $passwordVerifier,
    )
    {
    }


    /**
     * @throws \Throwable
     */
    public function deleteUser(string $token): void
    {
        $this->tracingService->trace(
            'user-delete',
            function (SpanInterface $span) use ($token) {
                $decoded = $this->jwtService->verifyToken($token);
                $user = $this->userRepository->findByEmail($decoded->email);
                $span->setAttribute('user.id', $user->id);
                $event = $this->userDeletedEventFactory->fromUser($user);
                $this->eventPublisher->publishDelete($event);
                $this->userRepository->delete($user);
            }
        );
    }
}
