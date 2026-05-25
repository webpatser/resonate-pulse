<?php

namespace Webpatser\ResonatePulse\Pulse;

use Laravel\Pulse\Pulse;
use Webpatser\ResonateWebhooks\Events\WebhookDelivered;
use Webpatser\ResonateWebhooks\Events\WebhookDropped;

/**
 * Records webhook delivery outcomes into Pulse storage.
 *
 * Listens for the two events that `webpatser/resonate-webhooks v0.2+` emits
 * from its dispatcher, and writes one record per event so Pulse buckets the
 * count per application across the chosen period.
 *
 *   `resonate_webhook_delivered`  (key = appId)   counts 2xx deliveries
 *   `resonate_webhook_failed`     (key = appId)   counts give-ups after retries
 */
class WebhooksRecorder
{
    /**
     * The events to listen for.
     *
     * @var array<int, class-string>
     */
    public array $listen = [
        WebhookDelivered::class,
        WebhookDropped::class,
    ];

    /**
     * Create a new recorder.
     */
    public function __construct(protected Pulse $pulse)
    {
        //
    }

    /**
     * Record one delivery outcome.
     */
    public function record(WebhookDelivered|WebhookDropped $event): void
    {
        $type = $event instanceof WebhookDelivered
            ? 'resonate_webhook_delivered'
            : 'resonate_webhook_failed';

        $this->pulse->record(type: $type, key: $event->appId, value: 1)->count();
    }
}
