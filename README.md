# Resonate Pulse

Laravel Pulse cards for the [Resonate](https://github.com/webpatser/resonate) plugin family. Four cards covering the cluster roster, webhook delivery, per-user-cap terminations, and token-auth rejections, each driven by a Pulse recorder that ingests on a beat or on an event.

## Cards

| Card | Source plugin (required to enable) | Recorder | Pulse series |
|------|------------------------------------|----------|--------------|
| **Roster** | `webpatser/resonate-roster` 0.7+ | `Pulse\RosterRecorder` (beat) | `resonate_roster_rooms`, `resonate_roster_users`, `resonate_roster_connections` |
| **Webhooks** | `webpatser/resonate-webhooks` 0.7+ | `Pulse\WebhooksRecorder` (events) | `resonate_webhook_delivered`, `resonate_webhook_failed` |
| **UserCap** | `webpatser/resonate-user-cap` 0.7+ | `Pulse\UserCapRecorder` (event) | `resonate_user_cap_exceeded` |
| **TokenAuth** | `webpatser/resonate-token-auth` 0.7+ | `Pulse\TokenAuthRecorder` (event) | `resonate_token_rejected` |

A recorder is only useful when its source plugin is installed. The pulse package does not hard-require the three event-driven plugins; the host opts in to each recorder via `config/pulse.php`, and you only register the cards you have data for.

## Requirements

- PHP 8.5+
- Laravel 13
- `laravel/pulse` 1.7+
- `webpatser/resonate-roster` 0.7+ (the Roster card and recorder always require this)

## Installation

```bash
composer require webpatser/resonate-pulse
```

Publish the config if you want to change the Roster sampling interval:

```bash
php artisan vendor:publish --tag=resonate-pulse-config
```

## Registering recorders

Add only the recorders for the plugins you have installed to `config/pulse.php`:

```php
'recorders' => [
    // ... your other recorders

    \Webpatser\ResonatePulse\Pulse\RosterRecorder::class => [],     // requires resonate-roster
    \Webpatser\ResonatePulse\Pulse\WebhooksRecorder::class => [],   // requires resonate-webhooks
    \Webpatser\ResonatePulse\Pulse\UserCapRecorder::class => [],    // requires resonate-user-cap
    \Webpatser\ResonatePulse\Pulse\TokenAuthRecorder::class => [],  // requires resonate-token-auth
],
```

Pulse takes care of subscribing each recorder to its `$listen` event (or, for `RosterRecorder`, the per-second `IsolatedBeat`). Your existing `pulse:check` schedule keeps running them.

## Adding cards to the dashboard

Publish the Pulse dashboard if you haven't yet:

```bash
php artisan vendor:publish --tag=pulse-dashboard
```

Drop the cards you want into `resources/views/vendor/pulse/dashboard.blade.php`:

```blade
<x-pulse>
    <livewire:resonate-pulse.roster cols="6" rows="2" />
    <livewire:resonate-pulse.webhooks cols="6" rows="2" />
    <livewire:resonate-pulse.user-cap cols="6" rows="2" />
    <livewire:resonate-pulse.token-auth cols="6" rows="2" />
    {{-- ...your other cards --}}
</x-pulse>
```

## Configuration

| Key | Default | Purpose |
|-----|---------|---------|
| `interval` | `15` | Roster sampling interval in seconds: the recorder samples once this many seconds have elapsed since its last sample. Any value works, including ones that do not divide 60. `0` or less turns the roster recorder off. The other recorders are event-driven and ignore this. |

## How the recorders write

- **Roster** is a *beat* recorder: each `IsolatedBeat` (one per second) is gated on elapsed time, and on each sample it takes one bulk snapshot of the roster and writes three series with avg + max aggregates.
- **Webhooks / UserCap / TokenAuth** are *event* recorders: each Laravel event becomes one Pulse record with `count` as the aggregate. Buckets are by application id (Webhooks, UserCap) or rejection reason (TokenAuth), so the cards can break the total down.

## How the roster snapshot reads Redis

`RosterMetrics` needs three things per beat and per dashboard poll: which channels are occupied, who is in them, and how many connections they hold. Asking the roster's per-channel read API costs a full keyspace `SCAN` per question, so gathering C channels cost `1 + 2C` scans. At 500 channels that is roughly 1000 full scans every 15 seconds, on the same Redis that carries the socket server's own traffic.

Roster 0.3 answers all of it in one call: `RoomRoster::snapshot()` does a single `SCAN` sweep plus one pipelined round trip of `HGETALL`s, because everything needed is already in those hashes (the values are the presence user ids, the field count is the connection count). The cost no longer scales with the number of channels.

`RosterSnapshot` is the thin adapter over that call: it asks the roster once per configured application and hands the result to `RosterMetrics`. Pulse holds no Redis connection and no key format of its own, so a roster schema change travels across on a composer update rather than breaking a card.

## Per-application figures

A roster belongs to one application. Before roster 0.3 the keyspace had no application dimension, so two applications that both served a `presence-lobby` were reported as one merged room, with one merged membership.

The Roster card now gathers per application and totals the result, so those are two rooms with two memberships, and the same user id in two applications counts as two people. When the server has more than one application configured, the card breaks the totals down per application and names the application beside each top room; a single-app server sees exactly what it saw before.

## License

MIT. See [LICENSE](LICENSE).
