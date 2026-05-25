<?php

namespace Webpatser\ResonatePulse\Pulse\Livewire;

use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Livewire\Concerns\HasPeriod;
use Laravel\Pulse\Livewire\Concerns\RemembersQueries;
use Livewire\Attributes\Lazy;

/**
 * The user-cap card: per-application terminations over the selected period.
 */
#[Lazy]
class UserCap extends Card
{
    use HasPeriod, RemembersQueries;

    /**
     * Render the component.
     */
    public function render()
    {
        [$readings, $time, $runAt] = $this->remember(function () {
            return $this->aggregate(['resonate_user_cap_exceeded'], 'count');
        });

        return view('resonate-pulse::livewire.user-cap', [
            'readings' => $readings,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
