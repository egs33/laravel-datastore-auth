<?php

return [
    'client_config' => [],
    'kind' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Cache config
    |--------------------------------------------------------------------------
    |
    | The cache is only used in fetch user data by id
    | (DatastoreUserProvider#retrieveById and DatastoreUserProvider#retrieveByToken).
    |
    |  ttl use seconds, null is forever.
    */
    'cache' => [
        'isEnabled' => false,
        'keyPrefix' => \DatastoreAuth\DatastoreUserProvider::class,
        'ttl' => null,
    ]
];
