<?php

namespace Tests\Small;

use DatastoreAuth\Facades\DatastoreAuth;
use DatastoreAuth\User;
use Google\Cloud\Datastore\Key;
use Mockery;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
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
        $this->assertInternalType('string', $user->getRememberTokenName());
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
