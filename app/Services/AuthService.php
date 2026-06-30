<?php

namespace App\Services;

use App\Factory\UserDeletedEventFactory;
use App\Kafka\EventPublisher;
use App\Repository\UserRepository;
use Throwable;

class AuthService
{
    public function __construct(
        private JWTService              $jwtService,
        private TracingService          $tracingService,
        private EventPublisher          $eventPublisher,
        private UserDeletedEventFactory $userDeletedEventFactory,
        private UserRepository          $userRepository,
    )
    {
    }


    /**
     * @throws Throwable
     */
    public function deleteUser(string $token): void
    {
        $this->tracingService->trace(
            'user.delete_profile',
            function ($span) use ($token) {
                $decoded = $this->jwtService->verifyToken($token);
                $user = $this->userRepository->findByEmail($decoded->email);
                $event = $this->userDeletedEventFactory->fromUser($user);

                $span->setAttribute('event.uuid', $event->metadata->eventUuid);
                $span->setAttribute('event.type', $event->metadata->eventType);
                $span->setAttribute('user.member_card.uuid', $user->member_card_uuid);

                $this->eventPublisher->publishDelete($event);
                $this->userRepository->delete($user);
            }
        );
    }
}
