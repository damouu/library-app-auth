<?php

namespace Tests\Services;

use App\Models\User;
use App\Services\AuthService;
use App\Services\JWTService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Junges\Kafka\Facades\Kafka;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AuthServiceTest extends TestCase
{

    /** @var AuthService */
    protected AuthService $authService;
    private MockObject $jwtServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtServiceMock = $this->createMock(JWTService::class);

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
        Kafka::fake();
        $this->jwtServiceMock->method('createToken')->willReturn('mock-jwt-token');

        $userMock = \Mockery::mock('alias:App\Models\User');
        $userMock->shouldReceive('create')->once();

        $dbUser = (object)[
            'id' => 'user-123',
            'email' => 'test@test.com',
            'user_name' => 'testuser',
            'card_uuid' => 'uuid-123'
        ];

        $userMock->shouldReceive('where')->andReturn(
            \Mockery::mock(['firstOrFail' => $dbUser])
        );

        $input = ['user_name' => 'testuser', 'email' => 'test@test.com', 'password' => 'secret'];
        $result = $this->authService->register($input);

        $this->assertEquals('uuid-123', $result['memberCardUUID']);
        Kafka::assertPublishedOn('auth-create-topic');

    }

    public function test_register_database_failure()
    {
        $userMock = \Mockery::mock('alias:App\Models\User');
        $userMock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('MongoDB Connection Failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('MongoDB Connection Failed');

        $input = ['user_name' => 'test', 'email' => 'test@test.com', 'password' => 'secret'];
        $this->authService->register($input);
    }

    public function test_register_kafka_failure()
    {
        $userMock = \Mockery::mock('alias:App\Models\User');
        $userMock->shouldReceive('create')->once();
        $dbUser = (object)['id' => '1', 'email' => 't@t.com', 'user_name' => 'u', 'card_uuid' => 'uuid'];
        $userMock->shouldReceive('where')->andReturn(\Mockery::mock(['firstOrFail' => $dbUser]));

        Kafka::fake();
        Kafka::shouldReceive('publish->onTopic->withHeaders->withKafkaKey->withBody->send')
            ->andThrow(new \Exception('Kafka Broker Unavailable'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kafka Broker Unavailable');

        $input = ['user_name' => 'u', 'email' => 't@t.com', 'password' => 'p'];
        $this->authService->register($input);
    }

    public function testLogin()
    {
        $email = 'test@example.com';
        $password = 'password123';
        $hashedPassword = 'hashed_password';
        $cardUuid = 'uuid-5555';
        $generatedToken = 'mock-jwt-token';

        Hash::shouldReceive('check')
            ->once()
            ->with($password, $hashedPassword)
            ->andReturn(true);

        $this->jwtServiceMock->method('createToken')
            ->willReturn($generatedToken);

        $userMock = \Mockery::mock('alias:' . User::class);

        $instanceMock = \Mockery::mock('stdClass');
        $userMock->shouldReceive('where')->with('email', $email)->andReturn($instanceMock);
        $instanceMock->shouldReceive('firstOrFail')->andReturn($instanceMock);


        $instanceMock->id = 'user-123';
        $instanceMock->card_uuid = $cardUuid;
        $instanceMock->password = $hashedPassword;
        $instanceMock->shouldReceive('save')->once()->andReturn(true);

        $result = $this->authService->login($email, $password);

        $this->assertEquals($cardUuid, $result['memberCardUUID']);
        $this->assertEquals($generatedToken, $result['jwt']);
        $this->assertEquals(3600, $result['expires_in']);
        $this->assertArrayHasKey('expires_at', $result);


    }


    public function test_login_throws_exception_on_wrong_password()
    {
        Hash::shouldReceive('check')->andReturn(false);

        $userMock = \Mockery::mock('alias:' . User::class);
        $instanceMock = \Mockery::mock('stdClass');
        $userMock->shouldReceive('where')->andReturn($instanceMock);
        $instanceMock->shouldReceive('firstOrFail')->andReturn($instanceMock);
        $instanceMock->password = 'hashed';

        $this->expectException(ValidationException::class);

        $this->authService->login('test@example.com', 'wrong-password');
    }


    public function testDeleteUser()
    {
        Kafka::fake();

        $jwtMock = $this->createMock(JWTService::class);
        $jwtMock->method('verifyToken')
            ->willReturn((object)['sub' => 'user-123']);

        $userMock = \Mockery::mock('alias:' . User::class);
        $userMock->shouldReceive('findOrFail')
            ->with('user-123')
            ->andReturn((object)[
                'id' => 'user-123',
                'card_uuid' => 'uuid-abcd-1234'
            ]);

        $service = new AuthService($jwtMock);

        $result = $service->deleteUser('valid-token');

        $this->assertEquals(200, $result);

        Kafka::assertPublishedOn('auth-delete-topic', null, function ($message) {
            $body = $message->getBody();
            return $message->getKey() === 'memberCardUUID' &&
                $body['memberCardUUID'] === 'uuid-abcd-1234';
        });


    }
}
