<?php

namespace Tests\Services;

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

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtServiceMock = $this->mock(JWTService::class);
        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->eventPublisherMock = $this->mock(EventPublisher::class);
        $this->userDeletedEventFactoryMock = $this->mock(UserDeletedEventFactory::class);
        $this->userRepositoryMock = $this->mock(UserRepository::class);
        $this->authService = $this->app->make(AuthService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_delete_user_executes_correctly()
    {
        $token = 'valid-jwt-token';
        $email = 'test@example.com';
        $spanMock = Mockery::mock(SpanInterface::class);

        $spanMock->shouldReceive('setAttribute')
            ->once()
            ->with('user.id', 'user-123');

        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with('user-delete', Mockery::type('Closure'))
            ->andReturnUsing(function ($spanName, $closure) use ($spanMock) {
                return $closure($spanMock);
            });

        $decodedToken = new stdClass();
        $decodedToken->email = $email;
        $this->jwtServiceMock->shouldReceive('verifyToken')
            ->once()
            ->with($token)
            ->andReturn($decodedToken);

        $mockUser = new User();
        $mockUser->id = 'user-123';
        $mockUser->email = $email;

        $this->userRepositoryMock->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($mockUser);


        $mockEvent = Mockery::mock(UserCreatedEventDTO::class);
        $this->userDeletedEventFactoryMock->shouldReceive('fromUser')
            ->once()
            ->with($mockUser)
            ->andReturn($mockEvent);

        $this->eventPublisherMock->shouldReceive('publishDelete')
            ->once()
            ->with($mockEvent);

        $this->userRepositoryMock->shouldReceive('delete')
            ->once()
            ->with($mockUser);

        $this->authService->deleteUser($token);

        $this->assertTrue(true);
    }
}
