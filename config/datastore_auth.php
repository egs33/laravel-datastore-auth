<?php

return [
    'client_config' => [],
    'kind' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Cache config
    |--------------------------------------------------------------------------
    |
    | If isEnabled is true, use cache in fetch user data by id
    | (DatastoreUserProvider#retrieveById and DatastoreUserProvider#retrieveByToken) only.
    |
    |  ttl use seconds, null is forever.
    */
    'cache' => [
        'isEnabled' => false,
        'keyPrefix' => \DatastoreAuth\DatastoreUserProvider::class,
        'ttl' => null,
    ]
];
