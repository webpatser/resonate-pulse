# Resonate Pulse

Laravel Pulse cards for the [Resonate](https://github.com/webpatser/resonate) plugin family. Currently ships one card visualizing the cluster-wide roster; more cards will follow as the other plugins (webhooks, user-cap, token-auth) expose counters.

## What you get in v0.1.0

A single `Resonate Roster` card on your Pulse dashboard:

| Metric | Source |
|--------|--------|
| Rooms occupied | Live count of `RoomRoster::occupiedChannels()` |
| Users online | Distinct presence user ids across all occupied channels |
| Connections | Sum of `RoomRoster::connectionCount()` across all channels |
| Top rooms | The 10 channels with the most distinct users |

A `RosterRecorder` also writes `resonate_roster_rooms`, `resonate_roster_users`, and `resonate_roster_connections` (avg + max) into Pulse storage on each beat, so a custom dashboard or Pulse SQL query can chart the trend over time.

## Requirements

- PHP 8.5+
- Laravel 13
- `laravel/pulse` 1.7+
- `webpatser/resonate-roster` 0.2+ (configured with `track => all` if you want non-presence channels counted as "rooms"; presence-only by default)

## Installation

```bash
composer require webpatser/resonate-pulse
```

Publish the config if you want to change the sampling interval:

```bash
php artisan vendor:publish --tag=resonate-pulse-config
```

## Registering the recorder

Add the recorder to `config/pulse.php`:

```php
'recorders' => [
    // ... your other recorders
    \Webpatser\ResonatePulse\Pulse\RosterRecorder::class => [],
],
```

That tells Pulse to instantiate the recorder and route `IsolatedBeat` events to it. Pulse's regular `pulse:check` schedule (or your own scheduler entry) takes care of dispatching the beat.

## Adding the card to the dashboard

Publish the Pulse dashboard if you haven't yet:

```bash
php artisan vendor:publish --tag=pulse-dashboard
```

Then drop the card into `resources/views/vendor/pulse/dashboard.blade.php`:

```blade
<x-pulse>
    <livewire:resonate-pulse.roster cols="6" rows="2" />
    {{-- ...your other cards --}}
</x-pulse>
```

## Configuration

| Key | Default | Purpose |
|-----|---------|---------|
| `interval` | `15` | Sampling interval in seconds. The recorder snapshots cluster state once per interval. |

## What's next

- A `Webhooks` card showing delivery throughput and failures (needs `resonate-webhooks` to emit counters).
- A `UserCap` card showing terminations (needs `resonate-user-cap` to emit a counter).
- A `TokenAuth` card showing rejections (needs `resonate-token-auth` to emit a counter).

These will land as their source plugins gain instrumentation.

## License

MIT. See [LICENSE](LICENSE).
