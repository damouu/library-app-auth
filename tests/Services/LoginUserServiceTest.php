<?php

namespace Tests\Services;

use App\Contracts\PasswordVerifier;
use App\DTO\JwtPayloadDTO;
use App\DTO\LoginRequestDTO;
use App\DTO\RegisterResponseDTO;
use App\Factory\JwtPayloadFactory;
use App\Models\User;
use App\Repository\UserRepository;
use App\Services\JWTService;
use App\Services\LoginUserService;
use App\Services\TracingService;
use App\Services\UserActivityService;
use Closure;
use Mockery;
use Mockery\MockInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use Tests\TestCase;

class LoginUserServiceTest extends TestCase
{
    private MockInterface $jwtServiceMock;
    private MockInterface $jwtPayloadFactoryMock;
    private MockInterface $passwordVerifierMock;
    private MockInterface $tracingServiceMock;
    private MockInterface $userRepositoryMock;
    private MockInterface $userActivityServiceMock;

    private LoginUserService $loginUserService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtServiceMock = $this->mock(JWTService::class);
        $this->jwtPayloadFactoryMock = $this->mock(JwtPayloadFactory::class);
        $this->passwordVerifierMock = $this->mock(PasswordVerifier::class);
        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->userRepositoryMock = $this->mock(UserRepository::class);
        $this->userActivityServiceMock = $this->mock(UserActivityService::class);

        $this->loginUserService = $this->app->make(LoginUserService::class);
    }

    public function test_login_authenticates_and_returns_token_dto(): void
    {
        $dto = new LoginRequestDTO(
            email: 'test@example.com',
            password: 'password123'
        );

        /**
         * Span mock (safe mode: ignore all missing methods except those explicitly tested)
         */
        $span = Mockery::mock(SpanInterface::class);
        $span->shouldIgnoreMissing();

        $this->tracingServiceMock
            ->shouldReceive('trace')
            ->once()
            ->with('user.login', Mockery::type(Closure::class))
            ->andReturnUsing(function ($name, $closure) use ($span) {
                return $closure($span);
            });

        $mockUser = new User([
            'id' => 'user-123',
            'password' => 'hashed_string',
            'card_uuid' => 'uuid-1234-5678',
        ]);

        $this->userRepositoryMock
            ->shouldReceive('findByEmail')
            ->once()
            ->with($dto->email)
            ->andReturn($mockUser);

        $this->passwordVerifierMock
            ->shouldReceive('verify')
            ->once()
            ->with($dto->password, $mockUser->password);

        $this->userActivityServiceMock
            ->shouldReceive('markLogin')
            ->once()
            ->with($mockUser);

        $mockPayload = new JwtPayloadDTO(
            issuer: 'your-issuer-domain',
            audience: 'your-app-audience',
            subject: 'user-123',
            memberCardUuid: 'uuid-1234-5678',
            avatarImgUrl: 'https://example.com/avatar.png',
            username: 'testuser',
            email: 'test@example.com'
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

        $result = $this->loginUserService->login($dto);

        $this->assertInstanceOf(RegisterResponseDTO::class, $result);
        $this->assertEquals('mock-jwt-token', $result->token);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
