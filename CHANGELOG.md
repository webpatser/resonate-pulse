# Changelog

All notable changes to `webpatser/resonate-pulse` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/webpatser/resonate-pulse/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/webpatser/resonate-pulse/releases/tag/v0.1.0
