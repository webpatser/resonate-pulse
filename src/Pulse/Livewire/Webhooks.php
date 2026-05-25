<?php

namespace Webpatser\ResonatePulse\Pulse\Livewire;

use Laravel\Pulse\Livewire\Card;
use Laravel\Pulse\Livewire\Concerns\HasPeriod;
use Laravel\Pulse\Livewire\Concerns\RemembersQueries;
use Livewire\Attributes\Lazy;

/**
 * The webhooks card: deliveries vs. failures over the selected period,
 * broken out by application id.
 */
#[Lazy]
class Webhooks extends Card
{
    use HasPeriod, RemembersQueries;

    /**
     * Render the component.
     */
    public function render()
    {
        [$readings, $time, $runAt] = $this->remember(function () {
            return [
                'delivered' => $this->aggregate(['resonate_webhook_delivered'], 'count'),
                'failed' => $this->aggregate(['resonate_webhook_failed'], 'count'),
            ];
        });

        return view('resonate-pulse::livewire.webhooks', [
            'readings' => $readings,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }
}
