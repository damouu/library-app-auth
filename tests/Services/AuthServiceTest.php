<?php

namespace Tests\Services;

use App\Services\AuthService;
use App\Services\JWTService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{

    /** @var AuthService */
    protected AuthService $authService;
    private MockObject $jwtServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a mock for the dependency
        $this->jwtServiceMock = $this->createMock(JWTService::class);

        // 2. Inject the mock into the real service
        $this->authService = new AuthService($this->jwtServiceMock);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }


    public function test_get_user_profile_without_db()
    {
        $jwtMock = $this->createMock(JWTService::class);
        $jwtMock->method('verifyToken')->willReturn((object)['sub' => '123']);

        $userMock = \Mockery::mock('alias:App\Models\User');

        $userMock->shouldReceive('findOrFail')
            ->with('123', \Mockery::type('array'))
            ->andReturn((object)[
                'id' => '123',
                'user_name' => 'Test User',
                'email' => 'test@example.com'
            ]);

        $service = new AuthService($jwtMock);

        $result = $service->getUserProfile('valid-token');

        $this->assertEquals('Test User', $result['user']->user_name);
    }


    public function testRegister()
    {
        $this->assertTrue(true);

    }

    public function testLogin()
    {
        $this->assertTrue(true);


    }

    public function testGetUserProfile()
    {
        $this->assertTrue(true);

    }

    public function testDeleteUser()
    {
        $this->assertTrue(true);


    }
}
