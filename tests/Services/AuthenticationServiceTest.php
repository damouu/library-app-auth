<?php

namespace Tests\Services;

use App\Services\AuthenticationService;
use App\Services\TracingService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthenticationServiceTest extends TestCase
{
    private MockInterface $tracingServiceMock;
    private AuthenticationService $authenticationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->authenticationService = $this->app->make(AuthenticationService::class);
    }

    public function test_verify_executes_successfully_when_password_matches(): void
    {
        $plainPassword = 'password123';
        $hashedPassword = 'hashed_password_string';

        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with('password-verification', Mockery::type('Closure'))
            ->andReturnUsing(fn($name, $closure) => $closure());

        Hash::shouldReceive('check')
            ->once()
            ->with($plainPassword, $hashedPassword)
            ->andReturn(true);

        $this->authenticationService->verify($plainPassword, $hashedPassword);
        $this->assertTrue(true);
    }

    public function test_verify_throws_validation_exception_when_password_is_incorrect(): void
    {
        $plainPassword = 'wrong_password';
        $hashedPassword = 'hashed_password_string';

        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with('password-verification', Mockery::type('Closure'))
            ->andReturnUsing(fn($name, $closure) => $closure());

        Hash::shouldReceive('check')
            ->once()
            ->with($plainPassword, $hashedPassword)
            ->andReturn(false);

        $this->expectException(ValidationException::class);

        try {
            $this->authenticationService->verify($plainPassword, $hashedPassword);
        } catch (ValidationException $e) {
            $this->assertEquals(
                ['email' => ['The provided credentials are incorrect.']],
                $e->errors()
            );
            throw $e;
        }
    }
}
