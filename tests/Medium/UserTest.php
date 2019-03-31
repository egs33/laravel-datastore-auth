<?php

namespace Tests\Medium;


use DatastoreAuth\DatastoreAuthServiceProvider;
use DatastoreAuth\DatastoreUserProvider;
use Google\Cloud\Datastore\DatastoreClient;
use Illuminate\Support\Facades\Auth;

class UserTest extends \Orchestra\Testbench\TestCase
{
    /**
     * @var string
     */
    static private $kind;

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [DatastoreAuthServiceProvider::class];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
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
     * @medium
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
        $this->assertInternalType('string', $user['password']);
        $this->assertTrue($userProvider->validateCredentials($user, ['password' => 'test-password']));
    }

    /**
     * @test
     * @medium
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
        $this->assertInternalType('string', $user['password']);
        $this->assertTrue($userProvider->validateCredentials($user, ['password' => 'test-password']));
    }

    /**
     * @test
     * @medium
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
     * @medium
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
}
