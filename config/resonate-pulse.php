<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recorder interval
    |--------------------------------------------------------------------------
    |
    | How often the roster recorder samples cluster state, in seconds. The
    | recorder listens for Pulse's `IsolatedBeat` event (one per second) and
    | records once this many seconds have elapsed since its last sample, so
    | any value means what it says (45 samples every 45 seconds, 90 every 90).
    | A value of 0 or less turns the roster recorder off.
    |
    */

    'interval' => (int) env('RESONATE_PULSE_INTERVAL', 15),

];
