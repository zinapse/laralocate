<?php

namespace Zinapse\LaraLocate;

use Illuminate\Support\ServiceProvider;
use Zinapse\LaraLocate\Commands\PopulateDatabase;

class LaraLocateServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        // Load the migrations
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        $this->publishes([
            __DIR__ . '/config/laralocate.php' => config_path('laralocate.php')
        ]);

        // Load the artisan command
        if ($this->app->runningInConsole())
        {
            $this->commands([
                PopulateDatabase::class
            ]);
        }
    }
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // $this->mergeConfigFrom(__DIR__.'/config/laralocate.php', 'laralocate');
    }
}