<?php

declare(strict_types=1);

namespace Patressz\LaravelModelDocumenter;

use Illuminate\Support\ServiceProvider;
use Patressz\LaravelModelDocumenter\Console\GenerateDocCommand;

final class LaravelDocumenterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateDocCommand::class,
            ]);
        }
    }
}
