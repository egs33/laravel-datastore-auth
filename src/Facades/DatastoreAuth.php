<?php

namespace DatastoreAuth\Facades;

use DatastoreAuth\DatastoreUserProvider;
use DatastoreAuth\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Facade;

/**
 * @package DatastoreAuth\Facades
 *
 * @method static User|null retrieveById(mixed $identifier)
 * @method static User|null retrieveByToken(mixed $identifier, string $token)
 * @method static void updateRememberToken(Authenticatable $user, string $token)
 * @method static User|null retrieveByCredentials(array $credentials)
 * @method static bool validateCredentials(Authenticatable $user, array $credentials)
 * @method static User create(array $data)
 * @method static string resetPassword(User $user, string $newPassword)
 * @method static string save(User $user)
 *
 * @see \DatastoreAuth\DatastoreUserProvider
 */
class DatastoreAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return DatastoreUserProvider::class;
    }

}
