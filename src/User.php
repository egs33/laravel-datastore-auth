<?php

namespace DatastoreAuth;

use Google\Cloud\Datastore\Entity;
use Illuminate\Contracts\Auth\Authenticatable;

class User extends Entity implements Authenticatable
{

    /**
     * @var string
     */
    protected $rememberTokenName = 'remember_token';

    /**
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return '__key__';
    }

    /**
     * @return string|int|null
     */
    public function getAuthIdentifier()
    {
        return $this->key()->pathEndIdentifier();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this['password'];
    }

    /**
     * @return string|null
     */
    public function getRememberToken(): ?string
    {
        if (!empty($this->getRememberTokenName())) {
            return (string)$this[$this->rememberTokenName];
        }
        return null;
    }

    /**
     * @param  string $value
     * @return void
     */
    public function setRememberToken($value): void
    {
        if (!empty($this->getRememberTokenName())) {
            $this[$this->rememberTokenName] = $value;
        }
    }

    /**
     * @return string
     */
    public function getRememberTokenName(): string
    {
        return $this->rememberTokenName;
    }
}
