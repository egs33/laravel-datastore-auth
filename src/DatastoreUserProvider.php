<?php

namespace DatastoreAuth;

use Google\Cloud\Datastore\DatastoreClient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DatastoreUserProvider implements UserProvider
{

    /**
     * @var DatastoreClient
     */
    private $datastoreClient;

    /**
     * @var Hasher
     */
    protected $hasher;

    /**
     * @var string
     */
    private $kind;

    /**
     * @var array
     */
    private $cacheConfig;

    /**
     * User constructor.
     * @param DatastoreClient $datastoreClient
     * @param Hasher $hasher
     * @param string $kind
     * @param array $cacheConfig [optional] {
     *     @type bool $isEnabled
     *     @type string $keyPrefix
     *     @type int|null $ttl
     * }
     */
    public function __construct(DatastoreClient $datastoreClient,
                                Hasher $hasher,
                                string $kind,
                                array $cacheConfig = [])
    {
        $this->datastoreClient = $datastoreClient;
        $this->hasher = $hasher;
        $this->kind = $kind;
        $this->cacheConfig = $cacheConfig + [
                'isEnabled' => false,
                'keyPrefix' => self::class . ':',
                'ttl' => null,
            ];
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @param string|int $identifier
     * @return string
     */
    private function composeCacheKey($identifier): string
    {
        return $this->cacheConfig['keyPrefix'] . $identifier;
    }

    /**
     * @param string|int $identifier
     * @return User|null
     */
    private function getCache($identifier): ?User
    {
        if (!$this->cacheConfig['isEnabled']) {
            return null;
        }
        $user = Cache::get($this->composeCacheKey($identifier));

        return $user instanceof User ? $user : null;
    }

    /**
     * @param string|int $identifier
     * @param User $user
     * @return bool
     */
    private function putCache($identifier, User $user): bool
    {
        if (!$this->cacheConfig['isEnabled']) {
            return false;
        }
        $ttl = $this->cacheConfig['ttl'] === null
            ? null
            : now()->addSeconds($this->cacheConfig['ttl']);

        return !!Cache::put($this->composeCacheKey($identifier), $user, $ttl);
    }

    /**
     * @param User|int|string $user
     * @return bool
     */
    public function deleteCache($user): bool
    {
        if (!$this->cacheConfig['isEnabled']) {
            return false;
        }
        $identifier = $user instanceof User ? $user->getAuthIdentifier() : $user;
        if (is_null($identifier)) {
            return false;
        }

        return !!Cache::forget($this->composeCacheKey($identifier));
    }

    /**
     * @param mixed $identifier
     * @return User|null
     */
    public function retrieveById($identifier): ?User
    {
        $cachedUser = $this->getCache($identifier);
        if ($cachedUser != null) {
            return $cachedUser;
        }
        $key = $this->datastoreClient->key($this->kind, $identifier);
        $user = $this->datastoreClient->lookup($key, ['className' => User::class]);
        if ($user != null) {
            $this->putCache($identifier, $user);
        }

        return $user;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param mixed $identifier
     * @param string $token
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, $token): ?User
    {
        $user = $this->retrieveById($identifier);

        return $user && $user->getRememberToken() && \hash_equals($user->getRememberToken(), $token) ? $user : null;
    }

    /**
     * @param Authenticatable $user
     * @param string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);
        $this->save($user);
    }

    /**
     * @param array $credentials
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials): ?User
    {
        if (empty($credentials) ||
            (\count($credentials) === 1 &&
                \array_key_exists('password', $credentials))) {
            return null;
        }

        $query = $this->datastoreClient->query();
        $query->kind($this->kind);
        $query->limit(1);

        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }
            $query->filter($key, '=', $value);
        }
        $result = $this->datastoreClient->runQuery($query, ['className' => User::class]);

        return $result->current();
    }

    /**
     * @param Authenticatable $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return $this->hasher->check($credentials['password'], $user->getAuthPassword());
    }

    /**
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        if (!\array_key_exists('password', $data)) {
            throw new \RuntimeException('key "password" is required');
        }
        $data['password'] = $this->hasher->make($data['password']);
        $key = $this->datastoreClient->allocateId($this->datastoreClient->key($this->kind));
        $entity = $this->datastoreClient->entity($key, $data, ['className' => User::class]);
        $this->datastoreClient->insert($entity);

        return $entity;
    }

    /**
     * @param User $user
     * @param string $newPassword
     * @return string
     */
    public function resetPassword(User $user, string $newPassword): string
    {
        $user['password'] = $this->hasher->make($newPassword);

        return $this->save($user);
    }

    /**
     * @param User $user
     * @return string
     */
    public function save(User $user): string
    {
        $version = $this->datastoreClient->update($user, ['allowOverwrite' => true]);
        $this->deleteCache($user);

        return $version;
    }
}
