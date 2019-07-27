<?php

return [
    'client_config' => [],
    'kind' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Cache config
    |--------------------------------------------------------------------------
    |
    | We use cache in fetch user data by id (DatastoreUserProvider#retrieveById and
    | DatastoreUserProvider#retrieveByToken) only.
    |
    |
    */
    'cache' => [
        'isEnabled' => false,
        'keyPrefix' => \DatastoreAuth\DatastoreUserProvider::class,
        'ttl' => null, // ttl use seconds, null is forever
    ]
];
