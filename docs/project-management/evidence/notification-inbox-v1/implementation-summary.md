# Implementation summary

- Table `larena_notifications` (package migration): id (uuid), recipient_ref, status, title, text, source,
  href, dedupe_key, repeat_count, created_at, shown_at, read_at.
- `Runtime\NotificationInbox`, scoped to one recipient for every read and change:
  - `record()` refuses an unknown status, an empty title, a malformed source, an `http://` link and links
    outside the site other than https; the same dedupe key within ten minutes refreshes the earlier entry;
  - `list()` with status, source, unread and search filters, newest first, with the total;
  - `unreadCount()`, `sources()`, `markRead()`, `markAllRead()`;
  - `takeUnshown()` hands each unread notification out once, for a pop-up;
  - `prune()` removes entries older than 30 days.
- `NotificationServiceProvider` (Composer-discovered): migration, the inbox binding, the
  `larena:notifications:prune` command and its daily schedule.
- No access operation: the inbox is personal; each person reads only what was addressed to them.
