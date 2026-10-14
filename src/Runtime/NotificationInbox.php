<?php

declare(strict_types=1);

namespace Larena\Notification\Runtime;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;

/**
 * The administrator notification inbox: what a person was told and has not necessarily seen yet.
 *
 * Each notification belongs to one recipient (an actor ref such as "user:admin_identity:7"); every read and
 * change is scoped to that recipient, so nobody reads or acknowledges someone else's notifications. The same
 * dedupe key recorded again within ten minutes refreshes the earlier notification instead of adding a copy.
 * Links stay inside the site or use https; plain http is refused.
 */
final class NotificationInbox
{
    public const TABLE = 'larena_notifications';

    public const STATUSES = ['info', 'success', 'warning', 'error'];

    public const RETENTION_DAYS = 30;

    private const DEDUPE_WINDOW_SECONDS = 600;

    /** @var Closure(): DateTimeImmutable */
    private Closure $clock;

    /** @param (Closure(): DateTimeImmutable)|null $clock */
    public function __construct(
        private readonly ConnectionInterface $connection,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? static fn (): DateTimeImmutable => new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function record(
        string $recipientRef,
        string $status,
        string $title,
        ?string $text,
        string $source,
        ?string $href = null,
        ?string $dedupeKey = null,
        bool $shown = false,
    ): InboxNotification {
        $recipientRef = $this->recipient($recipientRef);
        if (! in_array($status, self::STATUSES, true)) {
            throw new NotificationRejected('notification_status_invalid');
        }
        $title = $this->clip(trim($title), 200);
        if ($title === '') {
            throw new NotificationRejected('notification_title_required');
        }
        $text = $text === null || trim($text) === '' ? null : $this->clip(trim($text), 4000);
        if (preg_match('/^[a-z0-9][a-z0-9._:\/-]{0,119}$/', $source) !== 1) {
            throw new NotificationRejected('notification_source_invalid');
        }
        $href = $this->href($href);
        $dedupeKey = $dedupeKey === null || trim($dedupeKey) === '' ? null : $this->clip(trim($dedupeKey), 191);
        $now = $this->now();

        if ($dedupeKey !== null) {
            $since = $this->format($now->modify('-'.self::DEDUPE_WINDOW_SECONDS.' seconds'));
            $earlier = $this->table()
                ->where('recipient_ref', $recipientRef)
                ->where('dedupe_key', $dedupeKey)
                ->where('created_at', '>=', $since)
                ->orderByDesc('created_at')
                ->first();
            if ($earlier !== null) {
                $id = (string) $earlier->id;
                $this->table()->where('id', $id)->update([
                    'status' => $status,
                    'title' => $title,
                    'text' => $text,
                    'href' => $href,
                    'repeat_count' => (int) $earlier->repeat_count + 1,
                    'created_at' => $this->format($now),
                    'read_at' => null,
                ]);

                return $this->find($recipientRef, $id) ?? throw new NotificationRejected('notification_store_failed');
            }
        }

        $id = (string) Str::uuid();
        $this->table()->insert([
            'id' => $id,
            'recipient_ref' => $recipientRef,
            'status' => $status,
            'title' => $title,
            'text' => $text,
            'source' => $source,
            'href' => $href,
            'dedupe_key' => $dedupeKey,
            'repeat_count' => 1,
            'created_at' => $this->format($now),
            // A message the person has just seen on the page is not popped up again.
            'shown_at' => $shown ? $this->format($now) : null,
            'read_at' => null,
        ]);

        return $this->find($recipientRef, $id) ?? throw new NotificationRejected('notification_store_failed');
    }

    public function find(string $recipientRef, string $id): ?InboxNotification
    {
        $row = $this->mine($recipientRef)->where('id', $id)->first();

        return $row === null ? null : $this->item($row);
    }

    /**
     * A page of the recipient's notifications, newest first.
     *
     * @param array{status?: string|null, source?: string|null, unread?: bool, search?: string|null} $filter
     */
    public function list(string $recipientRef, array $filter = [], int $offset = 0, int $limit = 20): InboxPage
    {
        $query = $this->mine($recipientRef);
        $status = $filter['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }
        $source = $filter['source'] ?? null;
        if (is_string($source) && $source !== '') {
            $query->where('source', $source);
        }
        if (($filter['unread'] ?? false) === true) {
            $query->whereNull('read_at');
        }
        $search = trim((string) ($filter['search'] ?? ''));
        if ($search !== '') {
            // "!" escapes the wildcards: the same ESCAPE clause reads alike in SQLite and MySQL.
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(static function (Builder $match) use ($like): void {
                foreach (['title', 'text', 'source'] as $column) {
                    $match->orWhereRaw($column." like ? escape '!'", [$like]);
                }
            });
        }
        $total = (clone $query)->count();
        $rows = $query->orderByDesc('created_at')->orderByDesc('id')
            ->offset(max(0, $offset))->limit(max(1, min(200, $limit)))->get();

        return new InboxPage(array_values(array_map(fn (object $row): InboxNotification => $this->item($row), $rows->all())), $total);
    }

    public function unreadCount(string $recipientRef): int
    {
        return $this->mine($recipientRef)->whereNull('read_at')->count();
    }

    /** @return list<string> the sources the recipient has notifications from, for a filter */
    public function sources(string $recipientRef): array
    {
        return array_values(array_map('strval', $this->mine($recipientRef)->distinct()->orderBy('source')->pluck('source')->all()));
    }

    /** @param list<string> $ids @return int how many became read */
    public function markRead(string $recipientRef, array $ids): int
    {
        $ids = array_values(array_filter($ids, static fn (string $id): bool => $id !== ''));
        if ($ids === []) {
            return 0;
        }

        return $this->mine($recipientRef)->whereIn('id', $ids)->whereNull('read_at')->update(['read_at' => $this->format($this->now())]);
    }

    public function markAllRead(string $recipientRef): int
    {
        return $this->mine($recipientRef)->whereNull('read_at')->update(['read_at' => $this->format($this->now())]);
    }

    /**
     * Unread notifications the recipient has not been shown yet, oldest first, marked shown as they are
     * handed out: a page shows each of them once.
     *
     * @return list<InboxNotification>
     */
    public function takeUnshown(string $recipientRef, int $limit = 5): array
    {
        $rows = $this->mine($recipientRef)->whereNull('shown_at')->whereNull('read_at')
            ->orderBy('created_at')->limit(max(1, $limit))->get();
        $items = array_values(array_map(fn (object $row): InboxNotification => $this->item($row), $rows->all()));
        if ($items !== []) {
            $this->mine($recipientRef)->whereIn('id', array_map(static fn (InboxNotification $item): string => $item->id, $items))
                ->update(['shown_at' => $this->format($this->now())]);
        }

        return $items;
    }

    /** Removes notifications older than the retention period. @return int how many were removed */
    public function prune(int $days = self::RETENTION_DAYS): int
    {
        $before = $this->format($this->now()->modify('-'.max(1, $days).' days'));

        return $this->table()->where('created_at', '<', $before)->delete();
    }

    private function table(): Builder
    {
        return $this->connection->table(self::TABLE);
    }

    private function mine(string $recipientRef): Builder
    {
        return $this->table()->where('recipient_ref', $this->recipient($recipientRef));
    }

    private function recipient(string $recipientRef): string
    {
        $recipientRef = trim($recipientRef);
        if ($recipientRef === '' || strlen($recipientRef) > 191) {
            throw new NotificationRejected('notification_recipient_invalid');
        }

        return $recipientRef;
    }

    private function href(?string $href): ?string
    {
        $href = $href === null ? '' : trim($href);
        if ($href === '') {
            return null;
        }
        if (strlen($href) > 500 || preg_match('/[\x00-\x20\x7f]/', $href) === 1) {
            throw new NotificationRejected('notification_href_invalid');
        }
        if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
            return $href;
        }
        if (str_starts_with(strtolower($href), 'https://')) {
            return $href;
        }

        throw new NotificationRejected(str_starts_with(strtolower($href), 'http://') ? 'notification_href_insecure' : 'notification_href_invalid');
    }

    private function clip(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? rtrim(mb_substr($value, 0, $length - 1)).'…' : $value;
    }

    private function now(): DateTimeImmutable
    {
        return ($this->clock)()->setTimezone(new DateTimeZone('UTC'));
    }

    private function format(DateTimeImmutable $time): string
    {
        return $time->format('Y-m-d H:i:s');
    }

    private function item(object $row): InboxNotification
    {
        return new InboxNotification(
            (string) $row->id,
            (string) $row->recipient_ref,
            (string) $row->status,
            (string) $row->title,
            $row->text === null ? null : (string) $row->text,
            (string) $row->source,
            $row->href === null ? null : (string) $row->href,
            (int) $row->repeat_count,
            (string) $row->created_at,
            $row->shown_at === null ? null : (string) $row->shown_at,
            $row->read_at === null ? null : (string) $row->read_at,
        );
    }
}
