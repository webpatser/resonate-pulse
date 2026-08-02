# Changelog

All notable changes to `webpatser/resonate-pulse` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `RosterSnapshot`: the roster-facing side of the metrics gathering. It is
  registered as a singleton by `ResonatePulseServiceProvider` and is a thin
  adapter over `RoomRoster::snapshot()`, which returns every occupied channel of
  one application in a single `SCAN` sweep plus one pipelined batch of
  `HGETALL`s. Pulse holds no Redis connection and no key format of its own, so a
  roster schema change travels across on a composer update.

### Changed

- **Requires `webpatser/resonate-roster` 0.3+.** The roster key schema gained an
  application segment (`{prefix}:{appId}:{channel}:{node}`) and `RosterKeys`
  changed with it, so this release does not work against roster 0.2. The old
  `^0.2` constraint also excluded 0.3 outright under Composer's 0.x caret rules,
  which made the two uninstallable together. `webpatser/resonate` is now a direct
  dependency too: the cards resolve the configured applications through its
  `ApplicationProvider`.
- **API.** `RosterMetrics::__construct()` now takes a `RosterSnapshot` instead
  of a `RoomRoster`. Code that resolves `RosterMetrics` from the container (the
  card and the recorder both do) is unaffected; code that constructed it by
  hand needs the new argument.
- **Behaviour.** `RosterMetrics::gather()` reports per application and totals
  the result, instead of merging every application into one set of figures. Two
  applications that both serve a `presence-lobby` are two rooms with two
  memberships, and the same user id in each is two people; the old keyspace had
  no application dimension, so they were silently merged. `gather()` gains an
  `applications` key (`appId => ['rooms' => n, 'users' => n, 'connections' => n]`),
  and `top` is now a list of `['application' => ..., 'channel' => ..., 'users' => n]`
  rather than a channel-keyed map, since a channel name alone no longer
  identifies a room. The Roster card shows the per-application breakdown and an
  application column only when the server has more than one application, so a
  single-app dashboard looks unchanged.
- **Behaviour.** `RosterMetrics::gather()` no longer costs `1 + 2C` full
  keyspace scans for `C` occupied channels. It called `users()` and
  `connectionCount()` per channel and each of those was its own full `SCAN`, so
  at 500 channels a snapshot meant roughly 1000 scans, on every beat and every
  dashboard poll. A gather is now one sweep per application (two while the
  roster's `legacy_fallback` window is open) whose cost does not scale with the
  channel count.
- **Behaviour.** `RosterRecorder` gates sampling on elapsed time instead of
  `second % interval`. Second-of-minute modulo only lined up when the interval
  divided 60: an interval of 45 fired at `:00` and `:45` (alternating 45 and 15
  second gaps), and any interval of 60 or more could only match at `:00`, so it
  silently collapsed to one sample a minute. Intervals now mean what they say,
  and the first beat after start always samples rather than waiting for the
  minute's grid. An interval of `0` or less still disables the recorder.
### Removed

- The direct `predis/predis` dependency, along with the connection-parameter
  building and the keyspace scanning that came with it. All of that lives in
  `webpatser/resonate-roster`, which owns the schema; pulse asks it for a
  snapshot and does no Redis work of its own. Predis stays a dev dependency,
  because the test suite seeds Redis directly.

## [0.2.0] - 2026-05-25

### Added

- `Webhooks` card and `WebhooksRecorder`: charts delivery throughput and
  failures per application by listening for
  `Webpatser\ResonateWebhooks\Events\WebhookDelivered` and `WebhookDropped`
  (added in `webpatser/resonate-webhooks v0.2`). Series
  `resonate_webhook_delivered` and `resonate_webhook_failed`.
- `UserCap` card and `UserCapRecorder`: per-application termination count by
  listening for `Webpatser\ResonateUserCap\Events\UserCapExceeded` (added in
  `webpatser/resonate-user-cap v0.2`). Series `resonate_user_cap_exceeded`.
- `TokenAuth` card and `TokenAuthRecorder`: token rejection count broken out
  by reason (`missing_token`/`invalid_token`/`unauthorized_channel`) by
  listening for `Webpatser\ResonateTokenAuth\Events\TokenRejected` (added in
  `webpatser/resonate-token-auth v0.2`). Series `resonate_token_rejected`.
- README documents the recorder/card pairing and which plugin package each
  one requires; recorders are opted in via `config/pulse.php`, so an install
  with only a subset of plugins still gets the matching cards.

## [0.1.0] - 2026-05-25

Initial release.

### Added

- `Roster` Livewire card: shows cluster-wide rooms occupied, distinct users
  online, total connections, and a top-rooms table. Reads `RoomRoster` live
  so the numbers are always current.
- `RosterRecorder`: a Pulse recorder that listens for `IsolatedBeat` and
  samples cluster state on a configurable interval, writing the
  `resonate_roster_rooms`, `resonate_roster_users`, and
  `resonate_roster_connections` series (avg + max) into Pulse storage.
- `RosterMetrics`: pure gatherer that the recorder and the card share so
  the live view and the recorded series never drift.
- Configurable sampling interval (`RESONATE_PULSE_INTERVAL`, default 15s).
- Publishable config and views via `vendor:publish --tag=resonate-pulse-*`.

[Unreleased]: https://github.com/webpatser/resonate-pulse/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/webpatser/resonate-pulse/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/webpatser/resonate-pulse/releases/tag/v0.1.0
