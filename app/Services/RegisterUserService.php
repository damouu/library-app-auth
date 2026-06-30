<?php

namespace App\Services;

use App\DTO\RegisterResponseDTO;
use App\Factory\JwtPayloadFactory;
use App\Factory\UserCreatedEventFactory;
use App\Kafka\EventPublisher;
use App\Repository\UserRepository;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;
use Throwable;

class RegisterUserService
{

    public function __construct(
        private JWTService              $jwtService,
        private TracingService          $tracingService,
        private EventPublisher          $eventPublisher,
        private UserCreatedEventFactory $userCreatedEventFactory,
        private JwtPayloadFactory       $jwtPayloadFactory,
        private UserRepository          $userRepository,
        private AvatarUrlGenerator      $avatarUrlGenerator,
    )
    {
    }

    /**
     * @throws ValidationException|Exception|Throwable
     */
    public function register(array $validator): RegisterResponseDTO
    {
        return
            $this->tracingService->trace(
                'user.register',
                function ($span) use ($validator) {

                    $user = $this->userRepository->create([
                        'user_name' => $validator['user_name'],
                        'email' => $validator["email"],
                        'avatar_img_url' => $this->avatarUrlGenerator->generate($validator['user_name']),
                        'card_uuid' => Uuid::uuid4()->toString(),
                        'password' => Hash::make($validator['password']),
                    ]);

                    $event = $this->userCreatedEventFactory->fromUser($user);
                    $span->setAttribute('event.uuid', $event->metadata->eventUuid);
                    $span->setAttribute('event.type', $event->metadata->eventType);
                    $span->setAttribute('user.member_card.uuid', $event->data->memberCardUuid);
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
