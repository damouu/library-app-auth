<?php

namespace Tests\Services;

use App\DTO\UserProfileDTO;
use App\Models\User;
use App\Repository\UserRepository;
use App\Services\GetUserProfile;
use App\Services\JWTService;
use App\Services\TracingService;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;

class GetUserProfileTest extends TestCase
{
    private MockInterface $jwtServiceMock;
    private MockInterface $tracingServiceMock;
    private MockInterface $userRepositoryMock;
    private GetUserProfile $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtServiceMock = $this->mock(JWTService::class);
        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->userRepositoryMock = $this->mock(UserRepository::class);

        $this->service = $this->app->make(GetUserProfile::class);
    }

    public function test_get_user_profile_returns_dto(): void
    {
        $token = 'valid-token';

        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->withArgs(function ($name, $closure, $attributes = []) {
                return $name === 'user.get_profile' && is_callable($closure);
            })
            ->andReturnUsing(function ($name, $closure) {
                return $closure();
            });

        $decoded = new stdClass();
        $decoded->email = 'test@example.com';

        $this->jwtServiceMock->shouldReceive('verifyToken')
            ->once()
            ->with($token)
            ->andReturn($decoded);

        $user = new User([
            'user_name' => 'test',
            'email' => 'test@example.com',
            'avatar_img_url' => 'url',
            'card_uuid' => 'uuid'
        ]);

        $this->userRepositoryMock->shouldReceive('findByEmail')
            ->once()
            ->with('test@example.com')
            ->andReturn($user);

        $result = $this->service->getUserProfile($token);

        $this->assertInstanceOf(UserProfileDTO::class, $result);
    }
}
