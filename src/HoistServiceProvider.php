<?php

declare(strict_types=1);

namespace OffloadProject\Hoist;

use Illuminate\Support\ServiceProvider;
use OffloadProject\Hoist\Console\Command\FeatureMakeCommand;
use OffloadProject\Hoist\Services\FeatureDiscovery;

final class HoistServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/hoist.php',
            'hoist'
        );

        $this->app->singleton(FeatureDiscovery::class, function ($app) {
            return new FeatureDiscovery();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FeatureMakeCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/hoist.php' => config_path('hoist.php'),
            ], 'hoist-config');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/hoist-feature'),
            ], 'hoist-stubs');
        }
    }
}
