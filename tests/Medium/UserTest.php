<?php

namespace Tests\Medium;


use DatastoreAuth\DatastoreAuthServiceProvider;
use DatastoreAuth\DatastoreUserProvider;
use DatastoreAuth\User;
use Google\Cloud\Datastore\DatastoreClient;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Orchestra\Testbench\TestCase;

/**
 * Class UserTest
 * @package Tests\Medium
 * @medium
 */
class UserTest extends TestCase
{
    /**
     * @var string
     */
    static private $kind;

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [DatastoreAuthServiceProvider::class];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.driver', 'datastore');
        self::$kind = getenv('kind') ?: 'test_users';
        $app['config']->set('datastore_auth.kind', self::$kind);
    }

    protected function tearDown(): void
    {
        /** @var DatastoreClient $datastoreClient */
        $datastoreClient = $this->app->make(DatastoreClient::class);
        $query = $datastoreClient->query()->kind(self::$kind)->keysOnly();
        $keys = [];
        foreach ($datastoreClient->runQuery($query) as $result) {
            $keys[] = $result->key();
        }
        $datastoreClient->deleteBatch($keys);
        parent::tearDown();
    }


    /**
     * @test
     */
    public function testCreateUser(): void
    {
        /** @var DatastoreUserProvider $userProvider */
        $userProvider = Auth::createUserProvider('users');
        $createdUser = $userProvider->create([
            'password' => 'test-password',
            'name' => 'test_user',
            'email' => 'test_user@example.com',
            'created_at' => new \DateTime('2000-01-01T12:00:00Z'),
        ]);
        $this->assertEquals('test_user', $createdUser['name']);
        $this->assertEquals('test_user@example.com', $createdUser['email']);
        $this->assertEquals(new \DateTime('2000-01-01T12:00:00Z'), $createdUser['created_at']);
        $this->assertTrue($userProvider->validateCredentials($createdUser, ['password' => 'test-password']));

        $authIdentifier = $createdUser->getAuthIdentifier();
        $user = $userProvider->retrieveById($authIdentifier);
        $this->assertEquals('test_user', $user['name']);
        $this->assertEquals('test_user@example.com', $user['email']);
        $this->assertEquals(new \DateTime('2000-01-01T12:00:00Z'), $user['created_at']);
        $this->assertIsString($user['password']);
        $this->assertTrue($userProvider->validateCredentials($user, ['password' => 'test-password']));
    }

    /**
     * @test
     * @depends testCreateUser
     */
    public function testFindUser(): void
    {
        /** @var DatastoreUserProvider $userProvider */
        $userProvider = Auth::createUserProvider('users');
        $userProvider->create([
            'password' => 'test-password',
            'name' => 'test_user',
            'email' => 'test_user@example.com',
            'created_at' => new \DateTime('2000-01-01T12:00:00Z'),
        ]);

        $user = $userProvider->retrieveByCredentials(['name' => 'test_user']);

        $this->assertEquals('test_user', $user['name']);
        $this->assertEquals('test_user@example.com', $user['email']);
        $this->assertEquals(new \DateTime('2000-01-01T12:00:00Z'), $user['created_at']);
        $this->assertIsString($user['password']);
        $this->assertTrue($userProvider->validateCredentials($user, ['password' => 'test-password']));
    }

    /**
     * @test
     * @depends testCreateUser
     */
    public function testResetPassword(): void
    {
        /** @var DatastoreUserProvider $userProvider */
        $userProvider = Auth::createUserProvider('users');
        $createdUser = $userProvider->create([
            'password' => 'old-password',
            'name' => 'test_user2',
            'email' => 'test_user2@example.com'
        ]);

        $this->assertTrue($userProvider->validateCredentials($createdUser, ['password' => 'old-password']));
        $this->assertFalse($userProvider->validateCredentials($createdUser, ['password' => 'new-password']));

        $userProvider->resetPassword($createdUser, 'new-password');

        $this->assertTrue($userProvider->validateCredentials($createdUser, ['password' => 'new-password']));
        $this->assertFalse($userProvider->validateCredentials($createdUser, ['password' => 'old-password']));
    }

    /**
     * @test
     * @depends testCreateUser
     */
    public function testChangeAttributes(): void
    {
        /** @var DatastoreUserProvider $userProvider */
        $userProvider = Auth::createUserProvider('users');
        $createdUser = $userProvider->create([
            'password' => 'old-password',
            'name' => 'test_user3',
            'email' => 'test_user3@example.com',
            'group' => 'guest',
            'created_at' => new \DateTime('2000-01-01T12:00:00Z'),
            'updated_at' => new \DateTime('2000-01-01T12:00:00Z'),
        ]);

        $createdUser['email'] = 'test_user3_modified@example.com';
        $createdUser['updated_at'] = new \DateTime('2001-01-01T12:00:00Z');
        $createdUser['new_field'] = 'new_value';

        $createdUser->save();

        $user = $userProvider->retrieveById($createdUser->getAuthIdentifier());

        $this->assertEquals('test_user3', $user['name']);
        $this->assertEquals('test_user3_modified@example.com', $user['email']);
        $this->assertEquals('guest', $user['group']);
        $this->assertEquals(new \DateTime('2000-01-01T12:00:00Z'), $user['created_at']);
        $this->assertEquals(new \DateTime('2001-01-01T12:00:00Z'), $user['updated_at']);
        $this->assertEquals('new_value', $user['new_field']);
    }

    /**
     * @test
     * @depends testCreateUser
     */
    public function testAttempt(): void
    {
        /** @var DatastoreUserProvider $userProvider */
        $userProvider = Auth::createUserProvider('users');
        $userProvider->create([
            'password' => 'test-facade-password',
            'name' => 'test_facade_user',
            'email' => 'test_facade_user@example.com',
            'created_at' => new \DateTime('2000-01-01T12:00:00Z'),
        ]);

        $this->assertTrue(Auth::attempt([
            'password' => 'test-facade-password',
            'name' => 'test_facade_user',
        ]));

        $this->assertTrue(Auth::check());
        $user = Auth::user();
        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test_facade_user', $user['name']);
        $this->assertEquals('test_facade_user@example.com', $user['email']);
        $this->assertEquals(new \DateTime('2000-01-01T12:00:00Z'), $user['created_at']);
    }

    /**
     * @test
     * @depends testCreateUser
     */
    public function testAttemptWithInvalidCredential(): void
    {
        /** @var DatastoreUserProvider $userProvider */
        $userProvider = Auth::createUserProvider('users');
        $userProvider->create([
            'password' => 'test-facade-password',
            'name' => 'test_facade_user',
            'email' => 'test_facade_user@example.com',
            'created_at' => new \DateTime('2000-01-01T12:00:00Z'),
        ]);

        $this->assertFalse(Auth::attempt([
            'password' => 'invalid-password',
            'name' => 'test_facade_user',
        ]));

        $this->assertFalse(Auth::check());
        $user = Auth::user();
        $this->assertNull($user);

        $this->assertFalse(Auth::attempt([
            'password' => 'test-facade-password',
            'name' => 'fake_user',
        ]));

        $this->assertFalse(Auth::check());
        $user = Auth::user();
        $this->assertNull($user);
    }

    /**
     * @test
     * @depends testCreateUser
     */
    public function testLogin(): void
    {
        /** @var DatastoreUserProvider $userProvider */
        $userProvider = Auth::createUserProvider('users');
        $createdUser = $userProvider->create([
            'password' => 'test-facade-password',
            'name' => 'test_facade_user',
            'email' => 'test_facade_user@example.com',
            'created_at' => new \DateTime('2000-01-01T12:00:00Z'),
        ]);

        Auth::login($createdUser);
        $this->assertTrue(Auth::check());
        $user = Auth::user();
        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test_facade_user', $user['name']);
        $this->assertEquals('test_facade_user@example.com', $user['email']);
        $this->assertEquals(new \DateTime('2000-01-01T12:00:00Z'), $user['created_at']);
    }

    /**
     * @test
     * @depends testCreateUser
     */
    public function testLoginUsingId(): void
    {
        /** @var DatastoreUserProvider $userProvider */
        $userProvider = Auth::createUserProvider('users');
        $createdUser = $userProvider->create([
            'password' => 'test-facade-password',
            'name' => 'test_facade_user',
            'email' => 'test_facade_user@example.com',
            'created_at' => new \DateTime('2000-01-01T12:00:00Z'),
        ]);

        Auth::loginUsingId($createdUser->getAuthIdentifier());
        $this->assertTrue(Auth::check());
        $user = Auth::user();
        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test_facade_user', $user['name']);
        $this->assertEquals('test_facade_user@example.com', $user['email']);
        $this->assertEquals(new \DateTime('2000-01-01T12:00:00Z'), $user['created_at']);
    }
}
