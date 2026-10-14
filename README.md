# Larena Notification

Shared system package for transactional, system and operator notifications across Larena: event intake, recipient resolution, template rendering, channel routing, delivery, preferences, diagnostics and admin notification center integration.

## Inbox

The package stores administrator notifications: what a person was told and may not have seen yet.

- `Larena\Notification\Runtime\NotificationInbox` records a notification for one recipient (status `info`,
  `success`, `warning` or `error`, a title, optional text, a source `package:code` and an optional link inside
  the site or over https), lists and filters them, counts unread ones and marks them read.
- The same dedupe key within ten minutes refreshes the earlier notification instead of adding a copy.
- Notifications older than 30 days are removed daily by `php artisan larena:notifications:prune`.

Delivery channels (mail, messengers), templates and preferences are not implemented yet; the contracts in
`src/Contracts` describe them.

Canonical specifications are in `simai/larena-specs`.
