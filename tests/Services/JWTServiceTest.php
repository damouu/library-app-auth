<?php

namespace Tests\Services;

use App\Services\JWTService;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class JWTServiceTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $keyPath = base_path('keys');
        if (!file_exists($keyPath)) {
            mkdir($keyPath, 0777, true);
        }
    }

    public function test_can_create_and_verify_token()
    {
        $service = new JWTService();
        $payload = [
            'sub' => '1234567890',
            'name' => 'John Doe',
            'admin' => true
        ];

        $token = $service->createToken($payload);
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        $decoded = $service->verifyToken($token);

        $this->assertEquals('1234567890', $decoded->sub);
        $this->assertEquals('John Doe', $decoded->name);
        $this->assertTrue($decoded->admin);

        $this->assertObjectHasProperty('iat', $decoded);
        $this->assertObjectHasProperty('exp', $decoded);
    }
}
