<?php

namespace Webpatser\ResonatePulse\Pulse;

use Laravel\Pulse\Pulse;
use Webpatser\ResonateTokenAuth\Events\TokenRejected;

/**
 * Records token-auth rejections into Pulse storage.
 *
 * Listens for {@see TokenRejected} from `webpatser/resonate-token-auth v0.2+`
 * and counts one rejection per event, bucketed by reason so the dashboard can
 * show which rejection mode is firing.
 *
 *   `resonate_token_rejected`  (key = reason)   counts rejections by reason
 */
class TokenAuthRecorder
{
    /**
     * The event to listen for.
     *
     * @var class-string
     */
    public string $listen = TokenRejected::class;

    /**
     * Create a new recorder.
     */
    public function __construct(protected Pulse $pulse)
    {
        //
    }

    /**
     * Record one rejection.
     */
    public function record(TokenRejected $event): void
    {
        $this->pulse->record(type: 'resonate_token_rejected', key: $event->reason, value: 1)->count();
    }
}
