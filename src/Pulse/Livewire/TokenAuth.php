<?php

namespace Webpatser\ResonatePulse\Pulse\Livewire;

use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Livewire\Concerns\HasPeriod;
use Laravel\Pulse\Livewire\Concerns\RemembersQueries;
use Livewire\Attributes\Lazy;

/**
 * The token-auth card: rejections over the selected period, broken out by
 * the rejection reason (missing_token, invalid_token, unauthorized_channel).
 */
#[Lazy]
class TokenAuth extends Card
{
    use HasPeriod, RemembersQueries;

    /**
     * Render the component.
     */
    public function render()
    {
        [$readings, $time, $runAt] = $this->remember(function () {
            return $this->aggregate(['resonate_token_rejected'], 'count');
        });

        return view('resonate-pulse::livewire.token-auth', [
            'readings' => $readings,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
