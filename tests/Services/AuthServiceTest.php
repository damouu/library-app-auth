<?php

namespace Tests\Services;

use App\DTO\EventMetadataDTO;
use App\DTO\UserCreatedDataDTO;
use App\DTO\UserCreatedEventDTO;
use App\Factory\UserDeletedEventFactory;
use App\Kafka\EventPublisher;
use App\Models\User;
use App\Repository\UserRepository;
use App\Services\AuthService;
use App\Services\JWTService;
use App\Services\TracingService;
use Mockery;
use Mockery\MockInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use stdClass;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private MockInterface $jwtServiceMock;
    private MockInterface $tracingServiceMock;
    private MockInterface $eventPublisherMock;
    private MockInterface $userDeletedEventFactoryMock;
    private MockInterface $userRepositoryMock;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtServiceMock = $this->mock(JWTService::class);
        $this->eventPublisherMock = $this->mock(EventPublisher::class);
        $this->userDeletedEventFactoryMock = $this->mock(UserDeletedEventFactory::class);
        $this->userRepositoryMock = $this->mock(UserRepository::class);

        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->app->instance(TracingService::class, $this->tracingServiceMock);

        $this->authService = $this->app->make(AuthService::class);
    }

    public function test_delete_user_executes_correctly(): void
    {
        $token = 'valid-jwt-token';
        $email = 'test@example.com';

        /**
         * TRACE MOCK (robuste)
         */
        /**
         * TRACE MOCK (robuste)
         */
        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->withArgs(function ($name, $closure, $attributes = []) {
                return $name === 'user.delete_profile'
                    && is_callable($closure);
            })
            ->andReturnUsing(function ($name, $closure, $attributes = []) {

                $spanMock = Mockery::mock(SpanInterface::class);

                $spanMock->shouldReceive('setAttribute')->andReturnSelf();

                return $closure($spanMock);
            });

        /**
         * JWT decode
         */
        $decodedToken = new stdClass();
        $decodedToken->email = $email;

        $this->jwtServiceMock->shouldReceive('verifyToken')
            ->once()
            ->with($token)
            ->andReturn($decodedToken);

        /**
         * USER
         */
        $user = new User();
        $user->id = 'user-123';
        $user->email = $email;

        $this->userRepositoryMock->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($user);

        /**
         * EVENT DTO (FIX IMPORTANT : objet réel, pas mock)
         */
        $mockEvent = new UserCreatedEventDTO(
            metadata: new EventMetadataDTO(
                timestamp: '2026-01-01T00:00:00Z',
                sourceService: 'auth-service',
                eventType: 'user.deleted',
                eventUuid: 'event-uuid-123'
            ),
            data: new UserCreatedDataDTO(
                userName: 'test',
                email: $email,
                avatarImgUrl: 'https://example.com/avatar.png',
                memberCardUuid: 'card-123'
            )
        );

        $this->userDeletedEventFactoryMock->shouldReceive('fromUser')
            ->once()
            ->with($user)
            ->andReturn($mockEvent);

        /**
         * PUBLISH
         */
        $this->eventPublisherMock->shouldReceive('publishDelete')
            ->once()
            ->with($mockEvent);

        /**
         * DELETE
         */
        $this->userRepositoryMock->shouldReceive('delete')
            ->once()
            ->with($user);

        /**
         * EXEC
         */
        $this->authService->deleteUser($token);

        $this->assertTrue(true);
    }
}
