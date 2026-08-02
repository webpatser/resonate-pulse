<?php

namespace Webpatser\ResonatePulse\Tests\Support;

use Webpatser\ResonatePulse\RosterMetrics;
use Webpatser\ResonatePulse\RosterSnapshot;
use Webpatser\ResonateRoster\RoomRoster;

/**
 * A gatherer that answers from memory, so the sampling-gate tests exercise the
 * gate alone and never touch Redis.
 */
class StubRosterMetrics extends RosterMetrics
{
    /**
     * How many times a snapshot was asked for.
     */
    public int $gathered = 0;

    /**
     * Create a stub over a reader that is never used.
     */
    public function __construct()
    {
        parent::__construct(new RosterSnapshot(new RoomRoster([])));
    }

    /**
     * Return a fixed snapshot.
     *
     * @return array{
     *     rooms: int,
     *     users: int,
     *     connections: int,
     *     top: list<array{application: string, channel: string, users: int}>,
     *     applications: array<string, array{rooms: int, users: int, connections: int}>,
     * }
     */
    public function gather(int $topLimit = 10): array
    {
        $this->gathered++;

        return [
            'rooms' => 2,
            'users' => 3,
            'connections' => 4,
            'top' => [
                ['application' => 'app-id', 'channel' => 'presence-chat.1', 'users' => 2],
            ],
            'applications' => [
                'app-id' => ['rooms' => 2, 'users' => 3, 'connections' => 4],
            ],
        ];
    }
}
