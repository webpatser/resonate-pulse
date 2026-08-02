<?php

use Webpatser\ResonatePulse\Tests\TestCase;

uses(TestCase::class)->in(__DIR__.'/Feature');

/**
 * Determine whether a Redis server is reachable for the integration tests.
 */
function redisReachable(): bool
{
    $connection = @fsockopen('127.0.0.1', 6379, $errno, $errstr, 0.5);

    if ($connection === false) {
        return false;
    }

    fclose($connection);

    return true;
}

/**
 * Configure a second application alongside the first.
 *
 * It mirrors the first application, so both serve the same channel names,
 * which is the case the per-application figures have to keep apart.
 */
function withSecondApplication(string $appId = 'app-two', string $key = 'app-two-key', string $secret = 'app-two-secret'): void
{
    $apps = config('reverb.apps.apps');

    $second = $apps[0];
    $second['app_id'] = $appId;
    $second['key'] = $key;
    $second['secret'] = $secret;

    config()->set('reverb.apps.apps', [...$apps, $second]);
}
