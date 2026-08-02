<?php

use Predis\Client;
use Webpatser\ResonatePulse\RosterMetrics;

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
 * A gatherer wired exactly as the container builds it for the card.
 */
function makeMetrics(): RosterMetrics
{
    return app(RosterMetrics::class);
}

/**
 * Seed one socket of one application on one node.
 */
function seedSocket(Client $redis, string $channel, string $node, string $socket, string $user, string $appId = 'app-id'): void
{
    $redis->hset("roster-test:{$appId}:{$channel}:{$node}", $socket, $user);
}

it('returns zeros when the roster is empty', function () {
    $snapshot = makeMetrics()->gather();

    expect($snapshot)->toBe([
        'rooms' => 0,
        'users' => 0,
        'connections' => 0,
        'top' => [],
        'applications' => ['app-id' => ['rooms' => 0, 'users' => 0, 'connections' => 0]],
    ]);
});

it('counts rooms, users, and connections across the roster', function () {
    // presence-chat.1 on two nodes, three sockets, two distinct users
    seedSocket($this->redis, 'presence-chat.1', 'node-a', 'sock-1', 'u-alice');
    seedSocket($this->redis, 'presence-chat.1', 'node-a', 'sock-2', 'u-bob');
    seedSocket($this->redis, 'presence-chat.1', 'node-b', 'sock-3', 'u-alice');

    // presence-chat.2 on one node, one user
    seedSocket($this->redis, 'presence-chat.2', 'node-a', 'sock-4', 'u-carol');

    $snapshot = makeMetrics()->gather();

    expect($snapshot['rooms'])->toBe(2)
        ->and($snapshot['users'])->toBe(3) // alice, bob, carol cluster-wide
        ->and($snapshot['connections'])->toBe(4);
});

it('returns the top occupied channels in descending user order', function () {
    // chat.1 with 3 users
    foreach (['u-1', 'u-2', 'u-3'] as $i => $u) {
        seedSocket($this->redis, 'presence-chat.1', 'node-a', 'sock-1.'.$i, $u);
    }

    // chat.2 with 1 user
    seedSocket($this->redis, 'presence-chat.2', 'node-a', 'sock-2.0', 'u-1');

    // chat.3 with 2 users
    foreach (['u-4', 'u-5'] as $i => $u) {
        seedSocket($this->redis, 'presence-chat.3', 'node-a', 'sock-3.'.$i, $u);
    }

    $snapshot = makeMetrics()->gather();

    expect(array_column($snapshot['top'], 'channel'))
        ->toBe(['presence-chat.1', 'presence-chat.3', 'presence-chat.2'])
        ->and(array_column($snapshot['top'], 'users'))->toBe([3, 2, 1])
        ->and(array_column($snapshot['top'], 'application'))->toBe(['app-id', 'app-id', 'app-id']);
});

it('limits the top rooms list to the requested size', function () {
    foreach (range(1, 15) as $i) {
        seedSocket($this->redis, "presence-chat.{$i}", 'node-a', 'sock-1', 'u-'.$i);
    }

    $snapshot = makeMetrics()->gather(topLimit: 5);

    expect($snapshot['top'])->toHaveCount(5);
});

it('reports two applications separately rather than merging them', function () {
    withSecondApplication();

    // Both applications serve a "presence-lobby": before the roster carried the
    // application id these were one merged room with three members.
    seedSocket($this->redis, 'presence-lobby', 'node-a', 'sock-1', 'u-alice');
    seedSocket($this->redis, 'presence-lobby', 'node-a', 'sock-2', 'u-bob');
    seedSocket($this->redis, 'presence-lobby', 'node-a', 'sock-3', 'u-carol', appId: 'app-two');

    $snapshot = makeMetrics()->gather();

    expect($snapshot['rooms'])->toBe(2)
        ->and($snapshot['users'])->toBe(3)
        ->and($snapshot['connections'])->toBe(3)
        ->and($snapshot['applications'])->toBe([
            'app-id' => ['rooms' => 1, 'users' => 2, 'connections' => 2],
            'app-two' => ['rooms' => 1, 'users' => 1, 'connections' => 1],
        ])
        ->and($snapshot['top'])->toBe([
            ['application' => 'app-id', 'channel' => 'presence-lobby', 'users' => 2],
            ['application' => 'app-two', 'channel' => 'presence-lobby', 'users' => 1],
        ]);
});

it('counts the same user id in two applications as two people', function () {
    withSecondApplication();

    seedSocket($this->redis, 'presence-lobby', 'node-a', 'sock-1', 'u-7');
    seedSocket($this->redis, 'presence-lobby', 'node-a', 'sock-2', 'u-7', appId: 'app-two');

    expect(makeMetrics()->gather()['users'])->toBe(2);
});
