<?php

namespace DatastoreAuth;

use Google\Cloud\Datastore\Entity;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\RoutesNotifications;

class User extends Entity implements Authenticatable, CanResetPasswordContract, AuthorizableContract
{
    use Authorizable, CanResetPassword, RoutesNotifications;

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

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name === '__key__') {
            return $this->getAuthIdentifier();
        }
        return $this[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this[$name] = $value;
    }
}
