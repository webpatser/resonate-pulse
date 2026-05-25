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
