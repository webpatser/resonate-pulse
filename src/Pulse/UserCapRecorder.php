<?php

namespace Webpatser\ResonatePulse\Pulse;

use Laravel\Pulse\Pulse;
use Webpatser\ResonateUserCap\Events\UserCapExceeded;

/**
 * Records per-user-cap terminations into Pulse storage.
 *
 * Listens for {@see UserCapExceeded} from `webpatser/resonate-user-cap v0.2+`
 * and counts one termination per event, bucketed by application.
 *
 *   `resonate_user_cap_exceeded`  (key = appId)   counts terminations
 */
class UserCapRecorder
{
    /**
     * The event to listen for.
     *
     * @var class-string
     */
    public string $listen = UserCapExceeded::class;

    /**
     * Create a new recorder.
     */
    public function __construct(protected Pulse $pulse)
    {
        //
    }

    /**
     * Record one termination.
     */
    public function record(UserCapExceeded $event): void
    {
        $this->pulse->record(type: 'resonate_user_cap_exceeded', key: $event->appId, value: 1)->count();
    }
}
