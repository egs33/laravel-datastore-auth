<?php

namespace Tests\Small;

use DatastoreAuth\Facades\DatastoreAuth;
use DatastoreAuth\User;
use Google\Cloud\Datastore\Key;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version;

/**
 * Class UserTest
 * @package Tests\Small
 * @small
 */
class UserTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public static function assertIsString($actual, string $message = ''): void
    {
        if (Str::startsWith(Version::id(), '8')) {
            parent::assertIsString($actual, $message);
            return;
        }
        parent::assertInternalType('string', $actual, $message);
    }

    public function testGetAuthIdentifier()
    {
        $key = Mockery::mock(Key::class);
        $key->shouldReceive('pathEndIdentifier')->once()->andReturn('entity-key');
        $user = new User($key);
        $this->assertEquals('entity-key', $user->getAuthIdentifier());
    }

    public function testGetAuthPassword()
    {
        $key = Mockery::mock(Key::class);
        $user = new User($key, ['password' => 'test-password']);
        $this->assertEquals('test-password', $user->getAuthPassword());
        $user['password'] = 'new password';
        $this->assertEquals('new password', $user->getAuthPassword());
        $user->password = 'new password2';
        $this->assertEquals('new password2', $user->getAuthPassword());
    }

    public function testRememberToken()
    {
        $key = Mockery::mock(Key::class);
        $user = new User($key);
        $this->assertIsString($user->getRememberTokenName());
        $this->assertEmpty($user->getRememberToken());
        $user->setRememberToken('test-token');
        $this->assertEquals('test-token', $user->getRememberToken());
    }

    public function testGet()
    {
        $key = Mockery::mock(Key::class);
        $key->shouldReceive('pathEndIdentifier')->once()->andReturn('entity-key');
        $user = new User($key, ['key1' => 'value1']);
        $this->assertEquals('entity-key', $user->__key__);
        $this->assertEquals('value1', $user->key1);
    }

    public function testSave()
    {
        $user = new User(null, [
            'password' => 'pass',
            'name' => 'test user',
        ]);
        DatastoreAuth::shouldReceive('save')->once()->andReturn('2');

        $this->assertEquals('2', $user->save());
    }
}
