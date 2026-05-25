<?php

namespace Webpatser\ResonatePulse\Tests;

use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\PulseServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Testbench;
use Webpatser\ResonatePulse\ResonatePulseServiceProvider;
use Webpatser\ResonateRoster\RosterServiceProvider;

class TestCase extends Testbench
{
    /**
     * Get the package providers.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            PulseServiceProvider::class,
            RosterServiceProvider::class,
            ResonatePulseServiceProvider::class,
        ];
    }

    /**
     * Define the test environment.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('resonate-roster', [
            'connection' => [
                'url' => null,
                'host' => '127.0.0.1',
                'port' => '6379',
                'username' => null,
                'password' => null,
                'database' => '15',
                'timeout' => 5,
            ],
            'key_prefix' => 'roster-test',
            'ttl' => 90,
            'heartbeat_interval' => 30,
            'track' => 'all',
        ]);

        $app['config']->set('resonate-pulse.interval', 15);
    }
}
