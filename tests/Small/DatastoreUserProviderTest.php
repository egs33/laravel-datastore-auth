<?php

namespace Tests\Small;

use Carbon\Carbon;
use DatastoreAuth\DatastoreUserProvider;
use DatastoreAuth\User;
use Google\Cloud\Datastore\DatastoreClient;
use Google\Cloud\Datastore\EntityIterator;
use Google\Cloud\Datastore\Key;
use Google\Cloud\Datastore\Query\Query;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class DatastoreUserProviderTest
 * @package Tests\Small
 * @small
 */
class DatastoreUserProviderTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * @return User
     */
    private function createTestUser(): User
    {
        $user = new User();
        $user['name'] = 'test user';
        $user['email'] = 'test@example.com';

        return $user;
    }

    /**
     * @return Key
     */
    private function createTestKey(): Key
    {
        return new Key('test-project');
    }

    /**
     * @return MockInterface|DatastoreClient
     */
    private function createDatastoreClientMock()
    {
        $client = Mockery::mock(DatastoreClient::class);
        $client->shouldReceive('key')->andReturn($this->createTestKey());
        $client->shouldReceive('insert')->andReturn('');

        return $client;
    }

    /**
     * @return MockInterface|Hasher
     */
    private function createHasherMock()
    {
        return Mockery::mock(Hasher::class);
    }

    public function testGetKind()
    {
        $provider = new DatastoreUserProvider($this->createDatastoreClientMock(), $this->createHasherMock(), 'users_kind');
        $this->assertEquals('users_kind', $provider->getKind());
    }

    public function testRetrieveByIDReturnsUserWhenUserIsFound()
    {
        $client = $this->createDatastoreClientMock();
        $client->shouldReceive('lookup')->once()->andReturn($this->createTestUser());

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users');
        $user = $provider->retrieveById(1);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test user', $user['name']);
        $this->assertEquals('test@example.com', $user['email']);
    }

    public function testRetrieveByIDReturnsNullWhenUserIsNotFound()
    {
        $client = $this->createDatastoreClientMock();
        $client->shouldReceive('lookup')->once()->andReturn(null);

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users');
        $user = $provider->retrieveById(1);
        $this->assertNull($user);
    }

    public function testRetrieveByIDFetchFromCache()
    {
        $client = $this->createDatastoreClientMock();
        $client->shouldNotReceive('lookup');
        Cache::shouldReceive('get')
            ->once()
            ->with('test-prefix-user-id:1')
            ->andReturn($this->createTestUser());
        $cacheConfig = ['isEnabled' => true, 'keyPrefix' => 'test-prefix-user-id:'];

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users', $cacheConfig);
        $user = $provider->retrieveById(1);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test user', $user['name']);
        $this->assertEquals('test@example.com', $user['email']);
    }

    public function testRetrieveByIDStoreToCache()
    {
        $client = $this->createDatastoreClientMock();
        $user = $this->createTestUser();
        $client->shouldReceive('lookup')->once()->andReturn($user);
        Cache::shouldReceive('get')
            ->once()
            ->with('test-prefix-user-id:1')
            ->andReturn(null);
        Cache::shouldReceive('put')
            ->once()
            ->withArgs(function ($key, $value, $ttl) use ($user) {
                return $key === 'test-prefix-user-id:1'
                    && $value === $user
                    && $ttl instanceof Carbon;
            })
            ->andReturn(true);
        $cacheConfig = ['isEnabled' => true, 'keyPrefix' => 'test-prefix-user-id:', 'ttl' => 10];

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users', $cacheConfig);
        $user = $provider->retrieveById(1);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test user', $user['name']);
        $this->assertEquals('test@example.com', $user['email']);
    }

    public function testRetrieveByTokenReturnsUser()
    {
        $testUser = $this->createTestUser();
        $testUser->setRememberToken('token');
        $client = $this->createDatastoreClientMock();
        $client->shouldReceive('lookup')->once()->andReturn($testUser);

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users');
        $user = $provider->retrieveByToken(1, 'token');
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test user', $user['name']);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertEquals('token', $user->getRememberToken());
    }

    public function testRetrieveTokenWithBadIdentifierReturnsNull()
    {
        $client = $this->createDatastoreClientMock();
        $client->shouldReceive('lookup')->once()->andReturn(null);

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users');
        $user = $provider->retrieveByToken(1, 'token');
        $this->assertNull($user);
    }

    public function testRetrieveByBadTokenReturnsNull()
    {
        $testUser = $this->createTestUser();
        $testUser->setRememberToken('token');
        $client = $this->createDatastoreClientMock();
        $client->shouldReceive('lookup')->once()->andReturn($testUser);

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users');
        $user = $provider->retrieveByToken(1, 'bad-token');
        $this->assertNull($user);
    }

    public function testRetrieveByTokenFetchFromCache()
    {
        $testUser = $this->createTestUser();
        $testUser->setRememberToken('token');
        $client = $this->createDatastoreClientMock();
        $client->shouldNotReceive('lookup');
        Cache::shouldReceive('get')
            ->once()
            ->with('test-prefix-user-id:user-id')
            ->andReturn($testUser);
        $cacheConfig = ['isEnabled' => true, 'keyPrefix' => 'test-prefix-user-id:'];

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users', $cacheConfig);
        $user = $provider->retrieveByToken('user-id', 'token');
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test user', $user['name']);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertEquals('token', $user->getRememberToken());
    }

    public function testUpdateRememberToken()
    {
        $testUser = $this->createTestUser();
        $client = $this->createDatastoreClientMock();
        $client->shouldReceive('update')->once();

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users');
        $provider->updateRememberToken($testUser, 'new-token');
        $this->assertEquals('new-token', $testUser->getRememberToken());
    }

    public function testRetrieveByCredentialsReturnsUserWhenUserIsFound()
    {
        $client = $this->createDatastoreClientMock();
        $query = Mockery::mock(Query::class);
        $query->shouldReceive('kind')->once()->with('users');
        $query->shouldReceive('limit')->once()->with(1);
        $query->shouldReceive('filter')->once()->with('name', '=', 'test user');
        $query->shouldReceive('filter')->once()->with('email', '=', 'test@example.com');
        $entityIterator = Mockery::mock(EntityIterator::class);
        $entityIterator->shouldReceive('current')->once()->andReturn($this->createTestUser());
        $client->shouldReceive('query')->once()->andReturn($query);
        $client->shouldReceive('runQuery')->once()->andReturn($entityIterator);

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users');
        $user = $provider->retrieveByCredentials([
            'name' => 'test user',
            'password' => 'pass',
            'email' => 'test@example.com'
        ]);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test user', $user['name']);
        $this->assertEquals('test@example.com', $user['email']);
    }

    public function testRetrieveByCredentialsReturnsNullWhenUserIsFound()
    {
        $client = $this->createDatastoreClientMock();
        $query = Mockery::mock(Query::class);
        $query->shouldReceive('kind')->once()->with('users');
        $query->shouldReceive('limit')->once()->with(1);
        $query->shouldReceive('filter')->once()->with('name', '=', 'test user');
        $query->shouldReceive('filter')->once()->with('email', '=', 'test@example.com');
        $entityIterator = Mockery::mock(EntityIterator::class);
        $entityIterator->shouldReceive('current')->once()->andReturn(null);
        $client->shouldReceive('query')->once()->andReturn($query);
        $client->shouldReceive('runQuery')->once()->andReturn($entityIterator);

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users');
        $user = $provider->retrieveByCredentials([
            'name' => 'test user',
            'password' => 'pass',
            'email' => 'test@example.com'
        ]);
        $this->assertNull($user);
    }

    public function testRetrieveByCredentialsEmptyParameter()
    {
        $provider = new DatastoreUserProvider($this->createDatastoreClientMock(), $this->createHasherMock(), 'users');
        $user = $provider->retrieveByCredentials([]);
        $this->assertNull($user);
    }

    public function testCredentialValidation()
    {
        $hasher = $this->createHasherMock();
        $hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(true);
        $provider = new DatastoreUserProvider($this->createDatastoreClientMock(), $hasher, 'users');
        $user = $this->createTestUser();
        $user['password'] = 'hash';
        $result = $provider->validateCredentials($user, ['password' => 'plain']);
        $this->assertTrue($result);
    }

    public function testCreate()
    {
        $hasher = $this->createHasherMock();
        $hasher->shouldReceive('make')->once()->with('plain')->andReturn('hash');
        $client = $this->createDatastoreClientMock();
        $client->shouldReceive('entity')->once()
            ->with(\Hamcrest\Core\IsInstanceOf::anInstanceOf(Key::class), [
                'password' => 'hash',
                'name' => 'test user',
            ], ['className' => User::class])->andReturn($this->createTestUser());
        $client->shouldReceive('allocateId')->once()->andReturn($this->createTestKey());

        $provider = new DatastoreUserProvider($client, $hasher, 'users');
        $user = $provider->create([
            'password' => 'plain',
            'name' => 'test user',
        ]);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test user', $user['name']);
    }

    public function testCreateWithoutPassword()
    {
        $provider = new DatastoreUserProvider($this->createDatastoreClientMock(), $this->createHasherMock(), 'users');
        try {
            $provider->create([
                'name' => 'test user',
            ]);
            $this->assertTrue(false);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testChangePassword()
    {
        $hasher = $this->createHasherMock();
        $hasher->shouldReceive('make')->once()->with('new-password')->andReturn('new-hashed-password');
        $client = $this->createDatastoreClientMock();
        $client->shouldReceive('update')->once()->andReturn('');

        $provider = new DatastoreUserProvider($client, $hasher, 'users');
        $user = new User();
        $user->set([
            'password' => 'plain',
            'name' => 'test user',
            'other' => 'other-data'
        ]);
        $provider->resetPassword($user, 'new-password');

        $this->assertEquals('new-hashed-password', $user->password);
        $this->assertEquals('test user', $user->name);
        $this->assertEquals('other-data', $user->other);
    }

    public function testSave()
    {
        $user = new User(null, [
            'password' => 'pass',
            'name' => 'test user',
        ]);
        $client = $this->createDatastoreClientMock();
        $client->shouldReceive('update')->once()->andReturn('2');

        $provider = new DatastoreUserProvider($client, $this->createHasherMock(), 'users');
        $this->assertEquals('2', $provider->save($user));
    }
}
