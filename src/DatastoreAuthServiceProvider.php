<?php

namespace DatastoreAuth;

use Google\Cloud\Datastore\DatastoreClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class DatastoreAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::provider('datastore', function ($app, array $config) {
            return $app->make(DatastoreUserProvider::class);
        });
        $this->publishes([
            __DIR__ . '/../config/datastore_auth.php' => config_path('datastore_auth.php'),
        ]);
    }

    public function register()
    {
        $this->app->singleton(DatastoreClient::class, function ($app) {
            return new DatastoreClient(config('datastore_auth.client_config') ?? []);
        });
        $this->app->bind(DatastoreUserProvider::class, function ($app) {
            $kind = config('datastore_auth.kind') ?? 'users';

            return new DatastoreUserProvider($app->make(DatastoreClient::class), $app->make('hash'), $kind);
        });
    }
}
