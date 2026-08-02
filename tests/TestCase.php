<?php

namespace Webpatser\ResonatePulse\Tests;

use Illuminate\Support\ServiceProvider;
use Laravel\Pulse\PulseServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Testbench;
use Webpatser\Resonate\ResonateServiceProvider;
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
            ResonateServiceProvider::class,
            RosterServiceProvider::class,
            ResonatePulseServiceProvider::class,
        ];
    }

    /**
     * Define the test environment.
     *
     * The roster is scoped per application from 0.3, so the suite configures a
     * Resonate application (`app-id`) the way a host does, and points the
     * roster at Redis database 15, a throwaway it is free to flush.
     */
    protected function defineEnvironment($app): void
    {
        // Livewire signs its component payloads, so rendering a card needs a key.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('reverb.default', 'reverb');

        $app['config']->set('reverb.apps', [
            'provider' => 'config',
            'apps' => [
                [
                    'key' => 'app-key',
                    'secret' => 'app-secret',
                    'app_id' => 'app-id',
                    'options' => [
                        'host' => 'localhost',
                        'port' => 8080,
                        'scheme' => 'http',
                        'useTLS' => false,
                    ],
                    'allowed_origins' => ['*'],
                    'ping_interval' => 60,
                    'activity_timeout' => 30,
                    'max_connections' => null,
                    'max_message_size' => 10_000,
                ],
            ],
        ]);

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
