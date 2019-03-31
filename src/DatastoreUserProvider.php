<?php

namespace DatastoreAuth;

use Google\Cloud\Datastore\DatastoreClient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher;
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
     * User constructor.
     * @param DatastoreClient $datastoreClient
     * @param Hasher $hasher
     * @param string $kind
     */
    public function __construct(DatastoreClient $datastoreClient, Hasher $hasher, string $kind)
    {
        $this->datastoreClient = $datastoreClient;
        $this->hasher = $hasher;
        $this->kind = $kind;
    }

    /**
     * @param  mixed $identifier
     * @return User|null
     */
    public function retrieveById($identifier): ?User
    {
        $key = $this->datastoreClient->key($this->kind, $identifier);

        return $this->datastoreClient->lookup($key, ['className' => User::class]);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed $identifier
     * @param  string $token
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, $token): ?User
    {
        $user = $this->retrieveById($identifier);

        return $user && $user->getRememberToken() && \hash_equals($user->getRememberToken(), $token) ? $user : null;
    }

    /**
     * @param  Authenticatable $user
     * @param  string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);
        $this->datastoreClient->update($user, ['allowOverwrite' => true]);
    }

    /**
     * @param  array $credentials
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
     * @param  Authenticatable $user
     * @param  array $credentials
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

        return $this->datastoreClient->update($user, ['allowOverwrite' => true]);
    }

    /**
     * @param User $user
     * @return string
     */
    public function save(User $user): string
    {
        return $this->datastoreClient->update($user, ['allowOverwrite' => true]);
    }
}
