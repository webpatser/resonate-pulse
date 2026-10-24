# Upgrading

## From 0.7 to 1.0

1.0 changes constraints only; there are no API, config or behaviour changes.

1. Upgrade the whole Resonate family together: `webpatser/resonate` `^1.0`, `webpatser/resonate-roster` `^1.0`, and any of `resonate-token-auth`, `resonate-user-cap` and `resonate-webhooks` you use, all `^1.0`.
2. Require this package at `^1.0`:

   ```bash
   composer require webpatser/resonate-pulse:^1.0
   ```

3. Resonate 0.7 and its 0.7 plugin releases are no longer installable alongside this release.

The recorders and cards, `RosterSnapshot` and the `RosterMetrics::gather()` shape are unchanged. The `livewire/livewire` conflict rule (`>=4.3.0 <4.4.8`) from 0.7.1 stays.
