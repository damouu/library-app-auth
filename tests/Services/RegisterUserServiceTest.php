<?php

namespace Tests\Services;

use App\DTO\EventMetadataDTO;
use App\DTO\JwtPayloadDTO;
use App\DTO\RegisterResponseDTO;
use App\DTO\UserCreatedDataDTO;
use App\DTO\UserCreatedEventDTO;
use App\Factory\JwtPayloadFactory;
use App\Factory\UserCreatedEventFactory;
use App\Kafka\EventPublisher;
use App\Models\User;
use App\Services\JWTService;
use App\Services\RegisterUserService;
use App\Services\TracingService;
use App\Services\UserRegistrationService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RegisterUserServiceTest extends TestCase
{
    private MockInterface $jwtServiceMock;
    private MockInterface $tracingServiceMock;
    private MockInterface $eventPublisherMock;
    private MockInterface $userCreatedEventFactoryMock;
    private MockInterface $jwtPayloadFactoryMock;
    private MockInterface $userRegistrationServiceMock;
    private RegisterUserService $registerUserService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtServiceMock = $this->mock(JWTService::class);
        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->eventPublisherMock = $this->mock(EventPublisher::class);
        $this->userCreatedEventFactoryMock = $this->mock(UserCreatedEventFactory::class);
        $this->jwtPayloadFactoryMock = $this->mock(JwtPayloadFactory::class);
        $this->userRegistrationServiceMock = $this->mock(UserRegistrationService::class);
        $this->registerUserService = $this->app->make(RegisterUserService::class);
    }

    public function test_register_executes_successfully()
    {
        $input = ['user_name' => 'testuser', 'email' => 'test@test.com', 'password' => 'secret'];

        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with('user-registration', Mockery::type('Closure'))
            ->andReturnUsing(fn($span, $closure) => $closure());

        $mockUser = new User(['id' => 'user-123']);

        $this->userRegistrationServiceMock->shouldReceive('create')
            ->once()
            ->with($input)
            ->andReturn($mockUser);

        $mockEvent = new UserCreatedEventDTO(
            metadata: new EventMetadataDTO(
                timestamp: '2026-06-29T17:13:00Z',
                sourceService: 'auth-service',
                eventType: 'user.created',
                eventUuid: 'event-uuid-12345'
            ),
            data: new UserCreatedDataDTO(
                userName: 'testuser',
                email: 'test@example.com',
                avatarImgUrl: 'https://example.com/avatar.png',
                memberCardUuid: 'card-123'
            )
        );

        $this->userCreatedEventFactoryMock->shouldReceive('fromUser')
            ->once()
            ->with($mockUser)
            ->andReturn($mockEvent);

        $this->eventPublisherMock->shouldReceive('publish')->once()->with($mockEvent);

        $mockPayload = new JwtPayloadDTO(
            issuer: 'auth-service',
            audience: 'api',
            subject: $mockUser->id,
            memberCardUuid: 'card-123',
            avatarImgUrl: 'https://example.com/avatar.png',
            username: 'testuser',
            email: 'test@example.com'
        );

        $this->jwtPayloadFactoryMock->shouldReceive('fromUser')->once()->with($mockUser)->andReturn($mockPayload);
        $this->jwtServiceMock->shouldReceive('createToken')->once()->with($mockPayload)->andReturn('mock-jwt-token');

        $result = $this->registerUserService->register($input);

        $this->assertInstanceOf(RegisterResponseDTO::class, $result);
        $this->assertEquals('mock-jwt-token', $result->token);
    }
}
