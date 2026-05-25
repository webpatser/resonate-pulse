<?php

use Laravel\Pulse\Pulse;
use Webpatser\ResonatePulse\Pulse\TokenAuthRecorder;
use Webpatser\ResonatePulse\Pulse\UserCapRecorder;
use Webpatser\ResonatePulse\Pulse\WebhooksRecorder;
use Webpatser\ResonateTokenAuth\Events\TokenRejected;
use Webpatser\ResonateUserCap\Events\UserCapExceeded;
use Webpatser\ResonateWebhooks\Events\WebhookDelivered;
use Webpatser\ResonateWebhooks\Events\WebhookDropped;

it('listens for both webhook events', function () {
    $recorder = new WebhooksRecorder(app(Pulse::class));

    expect($recorder->listen)->toBe([WebhookDelivered::class, WebhookDropped::class]);
});

it('records a webhook delivery without error', function () {
    $recorder = new WebhooksRecorder(app(Pulse::class));

    $recorder->record(new WebhookDelivered('http://hook.test', 202, 'app-id', 1));
    $recorder->record(new WebhookDropped('http://hook.test', 'app-id', 5, 'HTTP 500'));

    expect(true)->toBeTrue(); // record() returns void; the assertion is that it doesn't throw.
});

it('listens for the user-cap event', function () {
    $recorder = new UserCapRecorder(app(Pulse::class));

    expect($recorder->listen)->toBe(UserCapExceeded::class);
});

it('records a user-cap termination without error', function () {
    $recorder = new UserCapRecorder(app(Pulse::class));

    $recorder->record(new UserCapExceeded('app-id', 'u-1'));

    expect(true)->toBeTrue();
});

it('listens for the token-auth event', function () {
    $recorder = new TokenAuthRecorder(app(Pulse::class));

    expect($recorder->listen)->toBe(TokenRejected::class);
});

it('records token rejections without error', function () {
    $recorder = new TokenAuthRecorder(app(Pulse::class));

    $recorder->record(new TokenRejected(TokenRejected::INVALID_TOKEN));
    $recorder->record(new TokenRejected(TokenRejected::MISSING_TOKEN));
    $recorder->record(new TokenRejected(TokenRejected::UNAUTHORIZED_CHANNEL));

    expect(true)->toBeTrue();
});
