<?php

namespace Webpatser\ResonatePulse\Pulse;

use Laravel\Pulse\Events\IsolatedBeat;
use Laravel\Pulse\Pulse;
use Webpatser\ResonatePulse\RosterMetrics;

/**
 * Records cluster-wide roster snapshots into Pulse storage.
 *
 * Listens for Pulse's per-second `IsolatedBeat` and samples once the configured
 * interval has elapsed since the last sample. Three series are written so the
 * dashboard can chart each one:
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
     * The beat timestamp of the last recorded sample, or null before the first.
     */
    protected ?int $lastRecordedAt = null;

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
     *
     * The gate is elapsed time, not second-of-minute. Sampling on
     * `second % $interval` only lines up when the interval divides 60: an
     * interval of 45 fired at :00 and :45, alternating 45 and 15 second gaps,
     * and anything from 60 up could only ever match at :00, silently collapsing
     * to one sample a minute. Any interval now means what it says.
     */
    public function record(IsolatedBeat $event): void
    {
        $interval = config()->integer('resonate-pulse.interval', 15);

        if ($interval <= 0) {
            return;
        }

        $timestamp = $event->time->getTimestamp();

        if ($this->lastRecordedAt !== null) {
            $elapsed = $timestamp - $this->lastRecordedAt;

            // A negative elapsed time means the clock moved backwards (an NTP
            // step, say). Re-anchor on this beat rather than going quiet until
            // wall-clock catches back up to the old mark.
            if ($elapsed >= 0 && $elapsed < $interval) {
                return;
            }
        }

        $this->lastRecordedAt = $timestamp;

        $snapshot = $this->metrics->gather();

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
