<?php

namespace Dawilly\Dawilly;

use Illuminate\Support\ServiceProvider;

class ClickpesaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/Config/clickpesa.php', 'clickpesa'
        );

        $this->app->singleton('clickpesa', function ($app) {
            return new Services\ClickpesaService(
                config('clickpesa.api_key'),
                config('clickpesa.client_id'),
                config('clickpesa.environment')
            );
        });

        $this->app->singleton('disbursement', function ($app) {
            $service = new Services\DisbursementService(
                config('clickpesa.api_key'),
                config('clickpesa.client_id'),
                config('clickpesa.environment')
            );
            
            // Share token between services
            $clickpesa = $app->make('clickpesa');
            if (method_exists($clickpesa, 'getToken')) {
                $token = $clickpesa->getToken();
                if ($token) {
                    $service->setToken($token);
                }
            }
            
            return $service;
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Config/clickpesa.php' => config_path('clickpesa.php'),
            ], 'clickpesa-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'clickpesa-migrations');
        }

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
