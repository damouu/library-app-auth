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
use App\Repository\UserRepository;
use App\Services\AvatarUrlGenerator;
use App\Services\JWTService;
use App\Services\RegisterUserService;
use App\Services\TracingService;
use Mockery;
use Mockery\MockInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class RegisterUserServiceTest extends TestCase
{
    private MockInterface $jwtServiceMock;
    private MockInterface $tracingServiceMock;
    private MockInterface $eventPublisherMock;
    private MockInterface $userCreatedEventFactoryMock;
    private MockInterface $jwtPayloadFactoryMock;
    private MockInterface $userRepositoryMock;
    private MockInterface $avatarUrlGeneratorMock;
    private MockInterface $spanMock;

    private RegisterUserService $registerUserService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtServiceMock = $this->mock(JWTService::class);
        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->eventPublisherMock = $this->mock(EventPublisher::class);
        $this->userCreatedEventFactoryMock = $this->mock(UserCreatedEventFactory::class);
        $this->jwtPayloadFactoryMock = $this->mock(JwtPayloadFactory::class);
        $this->userRepositoryMock = $this->mock(UserRepository::class);
        $this->avatarUrlGeneratorMock = $this->mock(AvatarUrlGenerator::class);

        $this->spanMock = Mockery::mock(SpanInterface::class);

        $this->registerUserService = $this->app->make(RegisterUserService::class);
    }

    public function test_register_executes_successfully(): void
    {
        $input = [
            'user_name' => 'testuser',
            'email' => 'test@test.com',
            'password' => 'secret',
        ];

        $this->tracingServiceMock
            ->shouldReceive('trace')
            ->once()
            ->with('user.register', Mockery::type(\Closure::class))
            ->andReturnUsing(
                fn($name, $closure) => $closure($this->spanMock)
            );

        $this->avatarUrlGeneratorMock
            ->shouldReceive('generate')
            ->once()
            ->with('testuser')
            ->andReturn('https://example.com/avatar.png');

        $mockUser = new User([
            'id' => Uuid::uuid4()->toString(),
            'user_name' => 'testuser',
            'email' => 'test@test.com',
            'card_uuid' => 'card-123',
            'avatar_img_url' => 'https://example.com/avatar.png',
        ]);

        $this->userRepositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($attributes) {
                return $attributes['user_name'] === 'testuser'
                    && $attributes['email'] === 'test@test.com'
                    && isset($attributes['password'])
                    && isset($attributes['card_uuid'])
                    && $attributes['avatar_img_url'] === 'https://example.com/avatar.png';
            }))
            ->andReturn($mockUser);

        $mockEvent = new UserCreatedEventDTO(
            metadata: new EventMetadataDTO(
                timestamp: '2026-06-29T17:13:00Z',
                sourceService: 'auth-service',
                eventType: 'USER_CREATED',
                eventUuid: 'event-uuid-12345',
            ),
            data: new UserCreatedDataDTO(
                userName: 'testuser',
                email: 'test@test.com',
                avatarImgUrl: 'https://example.com/avatar.png',
                memberCardUuid: 'card-123',
            ),
        );

        $this->userCreatedEventFactoryMock
            ->shouldReceive('fromUser')
            ->once()
            ->with($mockUser)
            ->andReturn($mockEvent);

        $this->spanMock
            ->shouldReceive('setAttribute')
            ->once()
            ->with('event.uuid', 'event-uuid-12345');

        $this->spanMock
            ->shouldReceive('setAttribute')
            ->once()
            ->with('event.type', 'USER_CREATED');

        $this->spanMock
            ->shouldReceive('setAttribute')
            ->once()
            ->with('user.member_card.uuid', 'card-123');

        $this->eventPublisherMock
            ->shouldReceive('publish')
            ->once()
            ->with($mockEvent);

        $mockPayload = new JwtPayloadDTO(
            issuer: 'auth-service',
            audience: 'api',
            subject: (string)$mockUser->id,
            memberCardUuid: 'card-123',
            avatarImgUrl: 'https://example.com/avatar.png',
            username: 'testuser',
            email: 'test@test.com',
        );

        $this->jwtPayloadFactoryMock
            ->shouldReceive('fromUser')
            ->once()
            ->with($mockUser)
            ->andReturn($mockPayload);

        $this->jwtServiceMock
            ->shouldReceive('createToken')
            ->once()
            ->with($mockPayload)
            ->andReturn('mock-jwt-token');

        $result = $this->registerUserService->register($input);

        $this->assertInstanceOf(RegisterResponseDTO::class, $result);
        $this->assertSame('mock-jwt-token', $result->token);
        $this->assertSame(3600, $result->expiresIn);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
