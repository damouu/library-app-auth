<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class UserTest extends TestCase
{

    public function test_user_attributes_are_cast_correctly()
    {
        $user = new User();

        $user->last_logged_in_at = '2026-01-21 12:00:00';
        $user->password = 'secret123';

        $this->assertInstanceOf(\DateTime::class, $user->last_logged_in_at);

        $this->assertNotEquals('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_sensitive_attributes_are_hidden_from_json()
    {
        $user = new User([
            'user_name' => 'testuser',
            'password' => 'secret123'
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayHasKey('user_name', $array);
    }

    public function test_mass_assignment_protection()
    {
        $user = new User([
            'user_name' => 'valid_name',
            'is_admin' => true
        ]);

        $this->assertEquals('valid_name', $user->user_name);
        $this->assertNull($user->is_admin);
    }
}
