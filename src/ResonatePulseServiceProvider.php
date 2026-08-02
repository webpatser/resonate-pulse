<?php

namespace Webpatser\ResonatePulse;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Webpatser\ResonatePulse\Pulse\Livewire\Roster;
use Webpatser\ResonatePulse\Pulse\Livewire\TokenAuth;
use Webpatser\ResonatePulse\Pulse\Livewire\UserCap;
use Webpatser\ResonatePulse\Pulse\Livewire\Webhooks;

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

        // The bulk reader is built from the roster's own connection config, so
        // the card and the recorder read the same Redis the roster writes to.
        $this->app->singleton(RosterSnapshot::class, function (Application $app): RosterSnapshot {
            $config = $app->make(Repository::class)->get('resonate-roster', []);

            return new RosterSnapshot(is_array($config) ? $config : []);
        });
    }

    /**
     * Bootstrap the package services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'resonate-pulse');

        Livewire::component('resonate-pulse.roster', Roster::class);
        Livewire::component('resonate-pulse.webhooks', Webhooks::class);
        Livewire::component('resonate-pulse.user-cap', UserCap::class);
        Livewire::component('resonate-pulse.token-auth', TokenAuth::class);

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
