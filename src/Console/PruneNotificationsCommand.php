<?php

declare(strict_types=1);

namespace Larena\Notification\Console;

use Illuminate\Console\Command;
use Larena\Notification\Runtime\NotificationInbox;

/** Removes notifications older than the retention period (30 days); scheduled daily. */
final class PruneNotificationsCommand extends Command
{
    protected $signature = 'larena:notifications:prune {--days=30 : Keep notifications this many days}';

    protected $description = 'Remove administrator notifications older than the retention period.';

    public function handle(NotificationInbox $inbox): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($days === false) {
            $this->error('notification_prune_days_invalid');

            return self::FAILURE;
        }
        $removed = $inbox->prune($days);
        $this->line('Removed '.$removed.' notification(s) older than '.$days.' day(s).');

        return self::SUCCESS;
    }
}
