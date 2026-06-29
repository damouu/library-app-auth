<?php

namespace Tests\Services;

use App\Models\User;
use App\Services\TracingService;
use App\Services\UserRegistrationService;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\MockInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use Tests\TestCase;

class UserRegistrationServiceTest extends TestCase
{
    private MockInterface $tracingServiceMock;
    private MockInterface $spanMock;
    private MockInterface $userModelMock; // Nouveau mock pour le modèle injecté
    private UserRegistrationService $userRegistrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->spanMock = Mockery::mock(SpanInterface::class);
        $this->userModelMock = $this->mock(User::class);
        $this->userRegistrationService = $this->app->make(UserRegistrationService::class);
    }

    public function test_create_registers_user_with_avatar_and_uuid_successfully(): void
    {
        $input = [
            'user_name' => 'dede',
            'email' => 'dede@example.com',
            'password' => 'secret123'
        ];

        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with('mongodb-insert-user', Mockery::type('Closure'))
            ->andReturnUsing(fn($name, $closure) => $closure($this->spanMock));

        Hash::shouldReceive('make')
            ->once()
            ->with('secret123')
            ->andReturn('hashed_secret123');

        $userInstanceMock = Mockery::mock(User::class);
        $userInstanceMock->shouldReceive('getAttribute')
            ->with('email')
            ->andReturn('dede@example.com');

        $this->userModelMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($attributes) {
                return $attributes['user_name'] === 'dede'
                    && $attributes['email'] === 'dede@example.com'
                    && $attributes['password'] === 'hashed_secret123'
                    && $attributes['avatar_img_url'] === 'https://avatar.iran.liara.run/username?username=dede+dede'
                    && !empty($attributes['card_uuid']);
            }))
            ->andReturn($userInstanceMock);

        $this->spanMock->shouldReceive('setAttribute')
            ->once()
            ->with('user.email', 'dede@example.com');

        $result = $this->userRegistrationService->create($input);
        $this->assertSame($userInstanceMock, $result);
    }
}
