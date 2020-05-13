<?php

namespace ymlluo\WxApi;

use Illuminate\Support\ServiceProvider;

class WxApiServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ymlluo');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'ymlluo');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wxapi.php', 'wxapi');

        $this->app->bind('wxapi', function ($app) {
            return new WxApi();
        });

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['wxapi'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/wxapi.php' => config_path('wxapi.php'),
        ], 'wxapi.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/ymlluo'),
        ], 'wxapi.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/ymlluo'),
        ], 'wxapi.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/ymlluo'),
        ], 'wxapi.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
