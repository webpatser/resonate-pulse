<?php

namespace Webpatser\ResonatePulse;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Webpatser\ResonatePulse\Pulse\Livewire\Roster;

/**
 * Wires the resonate-pulse cards into a host Laravel application.
 *
 * Loads the package views, registers the Livewire card, and publishes the
 * config. The Pulse recorder is registered by the host in `config/pulse.php`:
 *
 *     'recorders' => [
 *         \Webpatser\ResonatePulse\Pulse\RosterRecorder::class => [],
 *     ],
 */
class ResonatePulseServiceProvider extends ServiceProvider
{
    /**
     * Register the package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/resonate-pulse.php', 'resonate-pulse');
    }

    /**
     * Bootstrap the package services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'resonate-pulse');

        Livewire::component('resonate-pulse.roster', Roster::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/resonate-pulse.php' => $this->app->configPath('resonate-pulse.php'),
            ], 'resonate-pulse-config');

            $this->publishes([
                __DIR__.'/../resources/views' => $this->app->resourcePath('views/vendor/resonate-pulse'),
            ], 'resonate-pulse-views');
        }
    }
}
