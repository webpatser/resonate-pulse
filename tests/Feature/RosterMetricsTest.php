<?php

use Predis\Client;
use Webpatser\ResonatePulse\RosterMetrics;
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

function makeMetrics(): RosterMetrics
{
    return new RosterMetrics(new RoomRoster(config('resonate-roster')));
}

it('returns zeros when the roster is empty', function () {
    $snapshot = makeMetrics()->gather();

    expect($snapshot)->toBe([
        'rooms' => 0,
        'users' => 0,
        'connections' => 0,
        'top' => [],
    ]);
});

it('counts rooms, users, and connections across the roster', function () {
    // presence-chat.1 on two nodes, three sockets, two distinct users
    $this->redis->hset('roster-test:presence-chat.1:node-a', 'sock-1', 'u-alice');
    $this->redis->hset('roster-test:presence-chat.1:node-a', 'sock-2', 'u-bob');
    $this->redis->hset('roster-test:presence-chat.1:node-b', 'sock-3', 'u-alice');

    // presence-chat.2 on one node, one user
    $this->redis->hset('roster-test:presence-chat.2:node-a', 'sock-4', 'u-carol');

    $snapshot = makeMetrics()->gather();

    expect($snapshot['rooms'])->toBe(2)
        ->and($snapshot['users'])->toBe(3) // alice, bob, carol cluster-wide
        ->and($snapshot['connections'])->toBe(4);
});

it('returns the top occupied channels in descending user order', function () {
    // chat.1 with 3 users
    foreach (['u-1', 'u-2', 'u-3'] as $i => $u) {
        $this->redis->hset('roster-test:presence-chat.1:node-a', 'sock-1.'.$i, $u);
    }

    // chat.2 with 1 user
    $this->redis->hset('roster-test:presence-chat.2:node-a', 'sock-2.0', 'u-1');

    // chat.3 with 2 users
    foreach (['u-4', 'u-5'] as $i => $u) {
        $this->redis->hset('roster-test:presence-chat.3:node-a', 'sock-3.'.$i, $u);
    }

    $snapshot = makeMetrics()->gather();

    expect(array_keys($snapshot['top']))
        ->toBe(['presence-chat.1', 'presence-chat.3', 'presence-chat.2'])
        ->and($snapshot['top']['presence-chat.1'])->toBe(3)
        ->and($snapshot['top']['presence-chat.3'])->toBe(2)
        ->and($snapshot['top']['presence-chat.2'])->toBe(1);
});

it('limits the top rooms list to the requested size', function () {
    foreach (range(1, 15) as $i) {
        $this->redis->hset("roster-test:presence-chat.{$i}:node-a", 'sock-1', 'u-'.$i);
    }

    $snapshot = makeMetrics()->gather(topLimit: 5);

    expect($snapshot['top'])->toHaveCount(5);
});
