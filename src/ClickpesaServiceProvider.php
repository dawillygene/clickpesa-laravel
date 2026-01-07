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
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/Config/clickpesa.php' => config_path('clickpesa.php'),
            ], 'clickpesa-config');
        }

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }
}
