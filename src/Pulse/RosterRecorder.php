<?php

namespace Webpatser\ResonatePulse\Pulse;

use Laravel\Pulse\Events\IsolatedBeat;
use Laravel\Pulse\Pulse;
use Webpatser\ResonatePulse\RosterMetrics;

/**
 * Records cluster-wide roster snapshots into Pulse storage.
 *
 * Listens for Pulse's per-second `IsolatedBeat` and samples on the configured
 * interval. Two series are written so the dashboard can chart each one:
 *
 *   `resonate_roster_rooms`        - occupied channels cluster-wide
 *   `resonate_roster_users`        - distinct presence users cluster-wide
 *   `resonate_roster_connections`  - total roster-tracked connections
 *
 * Both `avg` and `max` aggregates are recorded so the card can show typical
 * load alongside peaks.
 */
class RosterRecorder
{
    /**
     * The Pulse event to listen for.
     *
     * @var class-string
     */
    public string $listen = IsolatedBeat::class;

    /**
     * Create a new recorder.
     */
    public function __construct(
        protected Pulse $pulse,
        protected RosterMetrics $metrics,
    ) {
        //
    }

    /**
     * Record a snapshot.
     */
    public function record(IsolatedBeat $event): void
    {
        $interval = (int) config('resonate-pulse.interval', 15);

        if ($interval <= 0 || $event->time->second % $interval !== 0) {
            return;
        }

        $snapshot = $this->metrics->gather();
        $timestamp = $event->time->getTimestamp();

        foreach ([
            'resonate_roster_rooms' => $snapshot['rooms'],
            'resonate_roster_users' => $snapshot['users'],
            'resonate_roster_connections' => $snapshot['connections'],
        ] as $type => $value) {
            $this->pulse
                ->record(type: $type, key: 'roster', value: $value, timestamp: $timestamp)
                ->avg()
                ->max()
                ->onlyBuckets();
        }
    }
}
