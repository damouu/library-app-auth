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
    private GetUserProfile $getUserProfileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtServiceMock = $this->mock(JWTService::class);
        $this->tracingServiceMock = $this->mock(TracingService::class);
        $this->userRepositoryMock = $this->mock(UserRepository::class);
        $this->getUserProfileService = $this->app->make(GetUserProfile::class);
    }

    public function test_get_user_profile_returns_dto()
    {
        $token = 'valid-jwt-token';

        $this->tracingServiceMock->shouldReceive('trace')
            ->once()
            ->with('user-get-profile', Mockery::type('Closure'))
            ->andReturnUsing(fn($span, $closure) => $closure());

        // ✅ FIX: service expects email, not sub
        $decodedToken = new stdClass();
        $decodedToken->email = 'dede@example.com';

        $this->jwtServiceMock->shouldReceive('verifyToken')
            ->once()
            ->with($token)
            ->andReturn($decodedToken);

        $mockUser = new User([
            'user_name' => 'test-user',
            'email' => 'dede@example.com',
            'avatar_img_url' => 'https://avatar.url',
            'card_uuid' => 'some-uuid'
        ]);

        // ✅ FIX: method name must match service
        $this->userRepositoryMock->shouldReceive('findByEmail')
            ->once()
            ->with('dede@example.com')
            ->andReturn($mockUser);

        $result = $this->getUserProfileService->getUserProfile($token);
        $this->assertInstanceOf(UserProfileDTO::class, $result);
    }
}
