<?php

declare(strict_types=1);

namespace Larena\Notification\Tests\Feature;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\DatabaseManager;
use Larena\Notification\Runtime\NotificationInbox;
use Larena\Notification\Runtime\NotificationRejected;
use Larena\Notification\Tests\TestCase;

final class NotificationInboxTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = new DateTimeImmutable('2026-10-14 12:00:00', new DateTimeZone('UTC'));
    }

    public function test_records_and_lists_only_the_recipients_own_notifications_newest_first(): void
    {
        $inbox = $this->inbox();
        $inbox->record('user:admin_identity:1', 'warning', 'Диск почти заполнен', 'Осталось 5%', 'larena/storage:disk');
        $this->later(60);
        $inbox->record('user:admin_identity:1', 'error', 'Импорт не удался', null, 'larena/content:import', '/admin/content');
        $inbox->record('user:admin_identity:2', 'error', 'Чужое', null, 'larena/content:import');

        $page = $this->inbox()->list('user:admin_identity:1');

        self::assertSame(2, $page->total);
        self::assertSame(['Импорт не удался', 'Диск почти заполнен'], array_map(static fn ($item): string => $item->title, $page->items));
        self::assertSame('/admin/content', $page->items[0]->href);
        self::assertTrue($page->items[0]->unread());
        self::assertSame(2, $this->inbox()->unreadCount('user:admin_identity:1'));
        self::assertSame(['larena/content:import', 'larena/storage:disk'], $this->inbox()->sources('user:admin_identity:1'));
        self::assertNull($this->inbox()->find('user:admin_identity:2', $page->items[0]->id));
    }

    public function test_filters_by_status_source_unread_and_search(): void
    {
        $inbox = $this->inbox();
        $first = $inbox->record('user:admin_identity:1', 'warning', 'Диск почти заполнен', 'Осталось 5%', 'larena/storage:disk');
        $inbox->record('user:admin_identity:1', 'error', 'Импорт не удался', 'Файл 50%_done повреждён', 'larena/content:import');
        $inbox->markRead('user:admin_identity:1', [$first->id]);

        self::assertSame(1, $inbox->list('user:admin_identity:1', ['status' => 'warning'])->total);
        self::assertSame(1, $inbox->list('user:admin_identity:1', ['source' => 'larena/content:import'])->total);
        self::assertSame('Импорт не удался', $inbox->list('user:admin_identity:1', ['unread' => true])->items[0]->title);
        self::assertSame(1, $inbox->list('user:admin_identity:1', ['search' => '50%_'])->total);
        self::assertSame(0, $inbox->list('user:admin_identity:1', ['search' => '5%_'])->total);
        self::assertSame(1, $inbox->list('user:admin_identity:1', [], 1, 1)->items === [] ? 0 : 1);
    }

    public function test_the_same_dedupe_key_within_ten_minutes_refreshes_instead_of_adding_a_copy(): void
    {
        $inbox = $this->inbox();
        $first = $inbox->record('user:admin_identity:1', 'error', 'Бэкап не удался', null, 'larena/backup:run', null, 'backup:failed');
        $inbox->markRead('user:admin_identity:1', [$first->id]);
        $this->later(300);
        $again = $this->inbox()->record('user:admin_identity:1', 'error', 'Бэкап не удался снова', null, 'larena/backup:run', null, 'backup:failed');

        self::assertSame($first->id, $again->id);
        self::assertSame(2, $again->repeatCount);
        self::assertTrue($again->unread());
        self::assertSame('2026-10-14 12:05:00', $again->createdAt);
        self::assertSame(1, $this->inbox()->list('user:admin_identity:1')->total);

        $this->later(900);
        $third = $this->inbox()->record('user:admin_identity:1', 'error', 'Бэкап не удался', null, 'larena/backup:run', null, 'backup:failed');
        self::assertNotSame($first->id, $third->id);
        self::assertSame(2, $this->inbox()->list('user:admin_identity:1')->total);
    }

    public function test_read_and_read_all_touch_only_the_recipients_notifications(): void
    {
        $inbox = $this->inbox();
        $mine = $inbox->record('user:admin_identity:1', 'warning', 'Первое', null, 'larena/admin:page');
        $inbox->record('user:admin_identity:1', 'error', 'Второе', null, 'larena/admin:page');
        $theirs = $inbox->record('user:admin_identity:2', 'error', 'Чужое', null, 'larena/admin:page');

        self::assertSame(0, $inbox->markRead('user:admin_identity:1', [$theirs->id]));
        self::assertSame(1, $inbox->markRead('user:admin_identity:1', [$mine->id]));
        self::assertSame(1, $inbox->unreadCount('user:admin_identity:1'));
        self::assertSame(1, $inbox->markAllRead('user:admin_identity:1'));
        self::assertSame(0, $inbox->unreadCount('user:admin_identity:1'));
        self::assertSame(1, $inbox->unreadCount('user:admin_identity:2'));
    }

    public function test_unshown_notifications_are_handed_out_once(): void
    {
        $inbox = $this->inbox();
        $inbox->record('user:admin_identity:1', 'warning', 'Новое', null, 'larena/backup:run');

        $inbox->record('user:admin_identity:1', 'error', 'Seen on the page', null, 'page:admin', null, null, true);

        self::assertSame(['Новое'], array_map(static fn ($item): string => $item->title, $inbox->takeUnshown('user:admin_identity:1')));
        self::assertSame([], $inbox->takeUnshown('user:admin_identity:1'));
        self::assertSame(2, $inbox->unreadCount('user:admin_identity:1'));
    }

    public function test_refuses_bad_input_and_plain_http_links(): void
    {
        $cases = [
            'notification_status_invalid' => ['user:admin_identity:1', 'fatal', 'T', null, 'larena/a:b', null],
            'notification_title_required' => ['user:admin_identity:1', 'error', '  ', null, 'larena/a:b', null],
            'notification_source_invalid' => ['user:admin_identity:1', 'error', 'T', null, 'Larena A', null],
            'notification_recipient_invalid' => [' ', 'error', 'T', null, 'larena/a:b', null],
            'notification_href_insecure' => ['user:admin_identity:1', 'error', 'T', null, 'larena/a:b', 'http://example.com/'],
            'notification_href_invalid' => ['user:admin_identity:1', 'error', 'T', null, 'larena/a:b', '//example.com/'],
        ];
        foreach ($cases as $reason => $args) {
            try {
                $this->inbox()->record(...$args);
                self::fail('Expected '.$reason);
            } catch (NotificationRejected $rejected) {
                self::assertSame($reason, $rejected->getMessage());
            }
        }
        self::assertSame('https://example.com/x', $this->inbox()->record('user:admin_identity:1', 'error', 'T', null, 'larena/a:b', 'https://example.com/x')->href);
    }

    public function test_prune_removes_notifications_older_than_thirty_days(): void
    {
        $this->inbox()->record('user:admin_identity:1', 'warning', 'Старое', null, 'larena/a:b');
        $this->later(86400 * 20);
        $this->inbox()->record('user:admin_identity:1', 'warning', 'Свежее', null, 'larena/a:b');
        $this->later(86400 * 11);

        self::assertSame(1, $this->inbox()->prune());
        self::assertSame('Свежее', $this->inbox()->list('user:admin_identity:1')->items[0]->title);
    }

    public function test_the_provider_binds_the_inbox_and_schedules_the_daily_prune(): void
    {
        $this->app->make(NotificationInbox::class)->record('user:admin_identity:1', 'error', 'T', null, 'larena/a:b');
        self::assertSame(1, $this->app->make(NotificationInbox::class)->unreadCount('user:admin_identity:1'));

        $this->artisan('larena:notifications:prune')->assertSuccessful();
        $events = array_map(static fn ($event): string => (string) $event->command, $this->app->make(Schedule::class)->events());
        self::assertNotSame([], array_filter($events, static fn (string $command): bool => str_contains($command, 'larena:notifications:prune')));
    }

    private function inbox(): NotificationInbox
    {
        return new NotificationInbox($this->app->make(DatabaseManager::class)->connection(), fn (): DateTimeImmutable => $this->now);
    }

    private function later(int $seconds): void
    {
        $this->now = $this->now->modify('+'.$seconds.' seconds');
    }
}
