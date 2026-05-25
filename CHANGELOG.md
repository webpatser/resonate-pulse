# Changelog

All notable changes to `webpatser/resonate-pulse` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
