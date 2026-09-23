# Changelog

All notable changes to `webpatser/resonate-pulse` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.2] - 2026-09-23

### Changed

- Support `webpatser/resonate` v0.7.
- Allow `webpatser/resonate-webhooks` v0.5.

## [0.3.1] - 2026-09-02

### Changed

- Allow `webpatser/resonate-roster` 0.4, and the 0.4 releases of `resonate-webhooks`, `resonate-user-cap` and `resonate-token-auth` in the test matrix. The constraints stay open to 0.3.1, so an existing install is not forced to move the whole family at once.
- Verified against `webpatser/resonate` v0.6.1 and `webpatser/resonate-roster` 0.4.0. No behaviour change: the cards read through `RosterSnapshot`, and the roster's read-side pipelining is transparent to them.

## [0.3.0] - 2026-08-02

### Added

- `RosterSnapshot`: a thin adapter over `RoomRoster::snapshot()`, registered as a singleton by `ResonatePulseServiceProvider`. Pulse holds no Redis connection and no key format of its own, so a roster schema change now travels across on a composer update.
- `applications` key on `RosterMetrics::gather()`: `appId => ['rooms' => n, 'users' => n, 'connections' => n]`.

### Changed

- Allow `webpatser/resonate` v0.6, and require the 0.3.1 releases of `resonate-roster`, `resonate-webhooks`, `resonate-user-cap` and `resonate-token-auth`, which carry the same widening. The previous constraints could not resolve against the current server release.

- Require `webpatser/resonate-roster` `^0.3` (was `^0.2`), and add `webpatser/resonate` as a direct dependency for the `ApplicationProvider` the cards resolve applications through.
- Read roster metrics through `RosterSnapshot`: `RosterMetrics::__construct()` takes one instead of a `RoomRoster`. Container resolution is unaffected; hand-built instances need the new argument.
- Report `gather()` figures per application and total them, instead of merging every application into one set. Two applications serving a `presence-lobby` are two rooms with two memberships.
- Return `gather()['top']` as a list of `['application' => ..., 'channel' => ..., 'users' => n]` rather than a channel-keyed map, since a channel name alone no longer identifies a room.
- Show the per-application breakdown and an application column on the Roster card only when the server has more than one application, so a single-app dashboard is unchanged.
- Cut a gather from `1 + 2C` full keyspace scans for `C` occupied channels to one sweep per application (two while the roster's `legacy_fallback` window is open), so its cost no longer scales with the channel count.

### Removed

- The runtime `predis/predis` dependency, with the connection-parameter building and keyspace scanning that came with it. It stays a dev dependency, because the test suite seeds Redis directly.

### Fixed

- Gate `RosterRecorder` sampling on elapsed time instead of `second % interval`. Modulo only lined up when the interval divided 60: 45 alternated 45 and 15 second gaps, and anything from 60 up collapsed to one sample a minute. The first beat after start now always samples, and `0` or less still disables the recorder.

### Upgrading

Requires `webpatser/resonate-roster` 0.3+. The constraint moved from `^0.2` to `^0.3`, so Composer will not install this release alongside an older roster. Upgrade the wave together and follow the roster's upgrade procedure.

- Roster metrics read through `RoomRoster::snapshot()`. Pulse opens no Redis connection of its own, so the roster's `connection` config is the only one that applies.
- `predis/predis` moved from a runtime dependency to dev-only. A host that relied on pulse pulling it in must require it directly.
- Figures are per application and then totalled, where they were previously merged across applications. A single-app server sees no change. A multi-app server sees rooms and distinct users rise, because two applications serving a `presence-lobby` are two rooms with two memberships instead of one merged hash; total connections are unchanged.
- `gather()['top']` is a list, not a channel-keyed map. Code reading it by channel name needs updating.

## [0.2.1] - 2026-07-30

### Changed

- Run the suite against a real Redis service in CI, and add Pint and PHPStan as gates.

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

[0.3.2]: https://github.com/webpatser/resonate-pulse/compare/v0.3.1...v0.3.2
[0.3.0]: https://github.com/webpatser/resonate-pulse/compare/v0.2.1...v0.3.0
[0.2.1]: https://github.com/webpatser/resonate-pulse/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/webpatser/resonate-pulse/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/webpatser/resonate-pulse/releases/tag/v0.1.0
