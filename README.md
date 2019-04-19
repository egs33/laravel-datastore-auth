# Laravel Datastore Auth
[Laravel authentication](https://laravel.com/docs/master/authentication) using [Google Datastore](https://cloud.google.com/datastore/docs/)

[![CircleCI](https://circleci.com/gh/egs33/laravel-datastore-auth.svg?style=shield)](https://circleci.com/gh/egs33/laravel-datastore-auth)
[![Latest Stable Version](https://poser.pugx.org/egs33/laravel-datastore-auth/v/stable)](https://packagist.org/packages/egs33/laravel-datastore-auth)
[![License](https://poser.pugx.org/egs33/laravel-datastore-auth/license)](https://packagist.org/packages/egs33/laravel-datastore-auth)
[![codecov](https://codecov.io/gh/egs33/laravel-datastore-auth/branch/master/graph/badge.svg)](https://codecov.io/gh/egs33/laravel-datastore-auth)

## Requirements

- Laravel >= 5.5.0
- Composer

## Installation

    $ composer require egs33/laravel-datastore-auth
    $ php artisan vendor:publish --provider="DatastoreAuth\DatastoreAuthServiceProvider"

## Quick Start

Set authentication driver to `datastore`. 
For example, in `config/auth.php`
```php
    'providers' => [
        'users' => [
            'driver' => 'datastore'
        ]
    ],
```

Then it can use same as [Laravel authentication](https://laravel.com/docs/5.7/authentication)

## Usage

### Create user
```php
$userConfig = [
    'name' => 'hoge',
    'email' => 'hoge@example.com',
    'password' => 'secret'
];
$userProvider = Auth::createUserProvider('users');
$userProvider->create($userConfig);
// or
DatastoreAuth::create($userConfig);
```

### Get Current User etc.
Use `Auth` facade.
Same as [Laravel authentication](https://laravel.com/docs/5.7/authentication)
```php
$user = Auth::user(); // get current user
$isLoggedIn = Auth::check();
```

### Update User Data
```php
$user['name'] = 'new-name';
$user['group'] = 'new-group';
$user->save();
```

## Config

Config file is `config/datastore_auth.php`

Default is
```php
[
    'client_config' => [],
    'kind' => 'users',
]
```
`kind` is kind name of user table.

`client_config` is passed to constructor of `Google\Cloud\Datastore\DatastoreClient`.
Please see [document](https://googleapis.github.io/google-cloud-php/#/docs/cloud-datastore/v1.7.0/datastore/datastoreclient) of `google/cloud-datastore`.
