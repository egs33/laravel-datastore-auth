<?php

namespace DatastoreAuth;

use Google\Cloud\Datastore\DatastoreClient;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Illuminate\Support\Facades\Auth;

class DatastoreAuthServiceProvider extends AuthServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();
        Auth::provider('datastore', function ($app, array $config) {
            $datastoreConfig = config('datastore_auth.client_config') ?? [];
            $kind = config('datastore_auth.kind') ?? 'users';
            return new DatastoreUserProvider(new DatastoreClient($datastoreConfig), $app->make('hash'), $kind);
        });
        $this->publishes([
            __DIR__ . '/../config/datastore_auth.php' => config_path('datastore_auth.php'),
        ]);
    }
}
