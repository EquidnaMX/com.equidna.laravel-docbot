<?php

namespace Equidna\LaravelDocbot;

use Illuminate\Support\ServiceProvider;
use Equidna\LaravelDocbot\Console\Commands\GenerateCommands;
use Equidna\LaravelDocbot\Console\Commands\GenerateRoutes;

/**
 * LaravelDocbotServiceProvider
 *
 * Registers Docbot commands and publishes configuration for Laravel projects.
 *
 * @category Laravel
 * @package  Equidna\LaravelDocbot
 * @author   EquidnaMX <info@equidna.mx>
 * @license  MIT
 * @link     https://github.com/equidnaMX/com.equidna.laravel-docbot
 */
class LaravelDocbotServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/docbot.php',
            'docbot'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    GenerateCommands::class,
                    GenerateRoutes::class,
                ]
            );

            $this->publishes(
                [
                    __DIR__ . '/../config/docbot.php' => config_path('docbot.php'),
                ],
                'laravel-docbot:config'
            );
        }
    }
}
