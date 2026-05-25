<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recorder interval
    |--------------------------------------------------------------------------
    |
    | How often the roster recorder samples cluster state, in seconds. The
    | recorder listens for Pulse's `IsolatedBeat` event (one per second) and
    | records only when the second modulo this interval is zero.
    |
    */

    'interval' => (int) env('RESONATE_PULSE_INTERVAL', 15),

];
