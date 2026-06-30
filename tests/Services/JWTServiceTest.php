<?php

namespace Tests\Services;

use App\DTO\JwtPayloadDTO;
use App\Services\JWTService;
use App\Services\TracingService;
use Closure;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;

class JWTServiceTest extends TestCase
{
    private MockInterface $tracingServiceMock;
    private JWTService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->jwtService = $this->app->make(JWTService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_token_traces_and_encodes_successfully(): void
    {
        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with(
                'jwt.create.token',
                Mockery::type(Closure::class),
                Mockery::type('array')
            )
            ->andReturnUsing(function ($name, $closure) {
                return $closure();
            });

        $payload = new JwtPayloadDTO(
            issuer: 'library-auth-service',
            audience: 'library-api',
            subject: 'user-123',
            memberCardUuid: 'card-123',
            avatarImgUrl: 'https://example.com/avatar.png',
            username: 'testuser',
            email: 'dede@example.com',
        );

        $token = $this->jwtService->createToken($payload);

        $this->assertIsString($token);
    }

    /**
     * Happy Path: Verify that a valid token is correctly decoded and traced.
     */
    public function test_verify_token_traces_and_decodes_successfully(): void
    {
        $this->tracingServiceMock->shouldReceive('trace')
            ->with('jwt.create.token', Mockery::type(Closure::class), Mockery::any())
            ->andReturnUsing(function ($name, $closure) {
                return $closure();
            });

        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with(
                'jwt.verify',
                Mockery::type(Closure::class),
                Mockery::on(function ($attributes) {
                    return array_key_exists('jwt.algorithm', $attributes);
                })
            )
            ->andReturnUsing(function ($name, $closure) {
                return $closure();
            });

        $payload = new JwtPayloadDTO(
            issuer: 'library-auth-service',
            audience: 'library-api',
            subject: 'user-123',
            memberCardUuid: 'card-123',
            avatarImgUrl: 'https://example.com/avatar.png',
            username: 'testuser',
            email: 'dede@example.com',
        );
        $token = $this->jwtService->createToken($payload);

        $result = $this->jwtService->verifyToken($token);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals('user-123', $result->sub);
        $this->assertEquals('dede@example.com', $result->email);
    }

    /**
     * Edge Case: Verify that an invalid token bubbles up the correct JWT exception.
     */
    public function test_verify_token_throws_exception_for_invalid_token(): void
    {
        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with('jwt.verify', Mockery::type(Closure::class), Mockery::any())
            ->andReturnUsing(function ($name, $closure) {
                return $closure();
            });

        $invalidToken = 'this.is.an.invalid.token';

        $this->expectException(\Exception::class);

        $this->jwtService->verifyToken($invalidToken);
    }
}
