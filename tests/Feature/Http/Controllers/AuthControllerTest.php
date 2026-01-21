<?php

namespace Tests\Feature\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Validation\PresenceVerifierInterface;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    private MockInterface $authServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authServiceMock = $this->mock(AuthService::class);

        $verifier = Mockery::mock(PresenceVerifierInterface::class);

        $verifier->shouldReceive('getCount')->andReturn(0);

        $this->app->make('validator')->setPresenceVerifier($verifier);
    }

    public function test_register_returns_json_with_token()
    {
        $input = [
            'user_name' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $this->authServiceMock->shouldReceive('register')
            ->once()
            ->with(Mockery::subset(['user_name' => 'testuser']))
            ->andReturn([
                'expires_in' => 3600,
                'expires_at' => '2026-01-21 13:00:00',
                'jwt' => 'mock-jwt-token',
                'memberCardUUID' => 'uuid-123'
            ]);

        $response = $this->postJson('/api/auth/register', $input);

        $response->assertStatus(201)
            ->assertJson([
                'token_type' => 'Bearer',
                'access_token' => 'mock-jwt-token',
                'memberCardUUID' => 'uuid-123'
            ]);
    }

    public function test_login_returns_401_if_credentials_missing()
    {
        $response = $this->postJson('/api/auth/login');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Missing Authorization header']);
    }

    public function test_login_success()
    {
        $email = 'test@example.com';
        $password = 'password123';

        $this->authServiceMock->shouldReceive('login')
            ->once()
            ->with($email, $password)
            ->andReturn([
                'expires_in' => 3600,
                'expires_at' => '2026-01-21 13:00:00',
                'jwt' => 'mock-jwt-token',
                'memberCardUUID' => 'uuid-123'
            ]);

        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode("$email:$password"),
        ])->postJson('/api/auth/login');

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'memberCardUUID']);
    }

    public function test_get_user_profile_returns_user_data()
    {
        $token = 'mock-bearer-token';
        $mockProfile = [
            'user_name' => 'testuser',
            'email' => 'test@example.com',
            'avatar_img_url' => 'http://example.com/img.png',
            'card_uuid' => 'uuid-123'
        ];

        $this->authServiceMock->shouldReceive('getUserProfile')
            ->once()
            ->with($token)
            ->andReturn($mockProfile);

        $response = $this->withToken($token)
            ->getJson('/api/auth/profile');

        $response->assertStatus(200)
            ->assertExactJson($mockProfile);
    }

    public function test_login_returns_401_if_email_or_password_are_null()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode(":"),
        ])->postJson('/api/auth/login');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Missing credentials']);
    }

    public function test_delete_user_returns_correct_status()
    {
        $this->authServiceMock->shouldReceive('deleteUser')
            ->once()
            ->andReturn(204);

        $response = $this->withToken('mock-token')
            ->deleteJson('/api/auth/user');

        $response->assertStatus(204);
    }
}
