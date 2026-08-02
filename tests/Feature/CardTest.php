<?php

use Livewire\Livewire;
use Webpatser\ResonatePulse\Pulse\Livewire\Roster;
use Webpatser\ResonatePulse\RosterMetrics;
use Webpatser\ResonatePulse\Tests\Support\StubRosterMetrics;

it('renders the card', function () {
    app()->instance(RosterMetrics::class, new StubRosterMetrics);

    Livewire::test(Roster::class)->assertOk();
});
