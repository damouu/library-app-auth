<?php

namespace Tests\Feature\Http\Controllers;

use App\DTO\RegisterResponseDTO;
use App\DTO\UserProfileDTO;
use App\Services\AuthService;
use App\Services\GetUserProfile;
use App\Services\LoginUserService;
use App\Services\RegisterUserService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    private MockInterface $registerUserServiceMock;
    private MockInterface $loginUserServiceMock;
    private MockInterface $getUserProfileMock;
    private MockInterface $authServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerUserServiceMock = $this->mock(RegisterUserService::class);
        $this->loginUserServiceMock = $this->mock(LoginUserService::class);
        $this->getUserProfileMock = $this->mock(GetUserProfile::class);
        $this->authServiceMock = $this->mock(AuthService::class);
    }

    public function test_register_returns_token_successfully(): void
    {
        $payload = [
            'user_name' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $dto = RegisterResponseDTO::fromToken(
            token: 'jwt-token-123',
            expiresIn: 3600
        );

        $this->registerUserServiceMock
            ->shouldReceive('register')
            ->once()
            ->with(Mockery::subset($payload))
            ->andReturn($dto);

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'token_type' => 'Bearer',
                'access_token' => 'jwt-token-123',
                'expires_in' => 3600,
            ]);
    }

    public function test_login_returns_token_successfully(): void
    {
        $email = 'test@example.com';
        $password = 'password123';

        $dto = RegisterResponseDTO::fromToken(
            token: 'jwt-token-123',
            expiresIn: 3600
        );

        $this->loginUserServiceMock
            ->shouldReceive('login')
            ->once()
            ->with(Mockery::on(fn($dto) => $dto->email === $email && $dto->password === $password
            ))
            ->andReturn($dto);

        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode("$email:$password"),
        ])->postJson('/api/auth/login');

        $response->assertStatus(201)
            ->assertJson([
                'access_token' => 'jwt-token-123',
                'token_type' => 'Bearer',
            ]);
    }

    public function test_get_user_profile_returns_user_profile(): void
    {
        $token = 'mock-token';

        $dto = new UserProfileDTO(
            userName: 'testuser',
            avatarUrl: 'http://example.com/avatar.png',
            email: 'test@example.com',
            cardUuid: 'uuid-123',
            lastLoggedInAt: '2026-01-01'
        );

        $this->getUserProfileMock
            ->shouldReceive('getUserProfile')
            ->once()
            ->with($token)
            ->andReturn($dto);

        $response = $this->withToken($token)
            ->getJson('/api/auth/profile');

        $response->assertStatus(200)
            ->assertJson([
                'user_name' => 'testuser',
                'email' => 'test@example.com',
                'card_uuid' => 'uuid-123',
            ]);
    }

    public function test_delete_user_returns_no_content(): void
    {
        $token = 'mock-token';
        $this->authServiceMock
            ->shouldReceive('deleteUser')
            ->once()
            ->with($token);
        $response = $this->withToken($token)
            ->deleteJson('/api/auth/user');
        $response->assertNoContent();
    }

    public function test_login_returns_401_without_credentials(): void
    {
        $this->loginUserServiceMock->shouldNotReceive('login');
        $response = $this->postJson('/api/auth/login');
        $response->assertStatus(401);
    }
}
