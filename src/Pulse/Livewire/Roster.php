<?php

namespace Webpatser\ResonatePulse\Pulse\Livewire;

use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Livewire\Concerns\HasPeriod;
use Laravel\Pulse\Livewire\Concerns\RemembersQueries;
use Livewire\Attributes\Lazy;
use Webpatser\ResonatePulse\RosterMetrics;

/**
 * The roster card: live cluster-wide presence counts and a top-rooms table.
 *
 * Reads the roster live for the current numbers and the top occupied rooms.
 * The Pulse-recorded time series is intentionally not charted in v0.1.0;
 * the operator can still query `resonate_roster_rooms`/`_users` via Pulse's
 * own SQL or build a custom chart on top.
 */
#[Lazy]
class Roster extends Card
{
    use HasPeriod, RemembersQueries;

    /**
     * Render the component.
     */
    public function render(RosterMetrics $metrics)
    {
        [$snapshot, $time, $runAt] = $this->remember(fn () => $metrics->gather());

        return view('resonate-pulse::livewire.roster', [
            'snapshot' => $snapshot,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
