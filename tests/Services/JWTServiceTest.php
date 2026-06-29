<?php

namespace Tests\Services;

use App\Services\JWTService;
use App\Services\TracingService;
use Mockery;
use Mockery\MockInterface;
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

    public function test_create_token_traces_and_encodes_successfully()
    {
        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with('jwt-create-token', Mockery::type('Closure'))
            ->andReturnUsing(fn($span, $closure) => $closure());

        $payloadDtoMock = Mockery::mock(\App\DTO\JwtPayloadDTO::class);

        $payloadDtoMock->shouldReceive('toArray')
            ->once()
            ->andReturn([
                'iss' => 'library-auth-service',
                'sub' => 'user-123',
                'email' => 'dede@example.com'
            ]);

        $token = $this->jwtService->createToken($payloadDtoMock);
        $this->assertIsString($token);
    }
}
