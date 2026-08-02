<?php

use Predis\Client;
use Webpatser\Resonate\Contracts\ApplicationProvider;
use Webpatser\ResonatePulse\RosterSnapshot;
use Webpatser\ResonatePulse\Tests\Support\CountingClient;
use Webpatser\ResonateRoster\RoomRoster;

beforeEach(function () {
    if (! redisReachable()) {
        $this->markTestSkipped('Redis not reachable');
    }

    $this->redis = new Client(['host' => '127.0.0.1', 'port' => 6379, 'database' => 15]);

    foreach ($this->redis->keys('roster-test:*') as $key) {
        $this->redis->del($key);
    }
});

afterEach(function () {
    if (isset($this->redis)) {
        foreach ($this->redis->keys('roster-test:*') as $key) {
            $this->redis->del($key);
        }
    }
});

/**
 * Fill a channel with one socket per user, on a given node of an application.
 */
function seedChannel(Client $redis, string $channel, string $node, array $members, string $appId = 'app-id'): void
{
    foreach ($members as $socket => $user) {
        $redis->hset("roster-test:{$appId}:{$channel}:{$node}", (string) $socket, $user);
    }
}

/**
 * Build a snapshot reader whose roster talks through a counting client.
 */
function countingSnapshot(): array
{
    $client = new CountingClient(['host' => '127.0.0.1', 'port' => 6379, 'database' => 15]);

    $applications = app(ApplicationProvider::class);

    $roster = new RoomRoster(config('resonate-roster'), $applications, $client);

    return [new RosterSnapshot($roster, $applications), $client];
}

it('returns nothing when the roster is empty', function () {
    [$snapshot] = countingSnapshot();

    expect($snapshot->channels())->toBe([]);
});

it('groups users and connections per channel across nodes', function () {
    seedChannel($this->redis, 'presence-chat.1', 'node-a', ['sock-1' => 'u-alice', 'sock-2' => 'u-bob']);
    seedChannel($this->redis, 'presence-chat.1', 'node-b', ['sock-3' => 'u-alice']);
    seedChannel($this->redis, 'presence-chat.2', 'node-a', ['sock-4' => 'u-carol']);

    [$snapshot] = countingSnapshot();

    $channels = $snapshot->channels();

    expect($channels)->toHaveCount(2)
        ->and($channels['presence-chat.1']['connections'])->toBe(3)
        ->and($channels['presence-chat.1']['users'])->toEqualCanonicalizing(['u-alice', 'u-bob'])
        ->and($channels['presence-chat.2']['connections'])->toBe(1)
        ->and($channels['presence-chat.2']['users'])->toBe(['u-carol']);
});

it('counts a blank user id as a connection but not as a user', function () {
    seedChannel($this->redis, 'private-orders.1', 'node-a', ['sock-1' => '', 'sock-2' => '']);

    [$snapshot] = countingSnapshot();

    $channels = $snapshot->channels();

    expect($channels['private-orders.1']['connections'])->toBe(2)
        ->and($channels['private-orders.1']['users'])->toBe([]);
});

it('reads one application without seeing another', function () {
    withSecondApplication();

    seedChannel($this->redis, 'presence-lobby', 'node-a', ['sock-1' => 'u-alice']);
    seedChannel($this->redis, 'presence-lobby', 'node-a', ['sock-2' => 'u-bob'], appId: 'app-two');

    [$snapshot] = countingSnapshot();

    expect($snapshot->applications())->toBe(['app-id', 'app-two'])
        ->and($snapshot->channels('app-id')['presence-lobby']['users'])->toBe(['u-alice'])
        ->and($snapshot->channels('app-two')['presence-lobby']['users'])->toBe(['u-bob']);
});

it('still reads a node that writes pre-0.3.0 keys', function () {
    // No application segment: written by a node that has not been upgraded.
    $this->redis->hset('roster-test:presence-chat.1:node-old', 'sock-1', 'u-alice');

    [$snapshot] = countingSnapshot();

    expect($snapshot->channels()['presence-chat.1']['users'])->toBe(['u-alice']);
});

it('does not scan more as the number of channels grows', function () {
    // Two channels, two nodes each.
    foreach ([1, 2] as $i) {
        seedChannel($this->redis, "presence-chat.{$i}", 'node-a', ['sock-a' => "u-{$i}"]);
        seedChannel($this->redis, "presence-chat.{$i}", 'node-b', ['sock-b' => "u-{$i}b"]);
    }

    [$few, $fewClient] = countingSnapshot();

    expect($few->channels())->toHaveCount(2);

    $fewScans = $fewClient->callsTo('scan');

    // Now eight channels, same shape.
    foreach (range(3, 8) as $i) {
        seedChannel($this->redis, "presence-chat.{$i}", 'node-a', ['sock-a' => "u-{$i}"]);
        seedChannel($this->redis, "presence-chat.{$i}", 'node-b', ['sock-b' => "u-{$i}b"]);
    }

    [$many, $manyClient] = countingSnapshot();

    expect($many->channels())->toHaveCount(8);

    $manyScans = $manyClient->callsTo('scan');

    // The whole point of the bulk path: cost is a function of the keyspace
    // sweep, not of the channel count. Per-channel gathering would have cost
    // 1 + 2C scans here, so 5 and 17 rather than an unchanged handful. Two
    // sweeps are expected while the roster's legacy fallback window is open:
    // one over the application's keys, one over the pre-0.3.0 keyspace.
    expect($manyScans)->toBe($fewScans)
        ->and($manyScans)->toBeLessThanOrEqual(4)
        ->and($manyScans)->toBeGreaterThan(0);
});

it('reads every node hash in one pipelined round trip', function () {
    foreach (range(1, 4) as $i) {
        seedChannel($this->redis, "presence-chat.{$i}", 'node-a', ['sock-a' => "u-{$i}"]);
    }

    [$snapshot, $client] = countingSnapshot();

    $snapshot->channels();

    // HGETALL is queued on the pipeline, never issued command-by-command on
    // the client, so a per-key round trip would show up here.
    expect($client->callsTo('hgetall'))->toBe(0);
});
