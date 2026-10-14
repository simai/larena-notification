<?php

declare(strict_types=1);

namespace Larena\Notification\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use Larena\Notification\Console\PruneNotificationsCommand;
use Larena\Notification\Runtime\NotificationInbox;

/**
 * The notification inbox: its table, the inbox over the application's connection and the daily removal of
 * notifications older than 30 days. The inbox is personal — each person reads only their own — so it adds no
 * access operation; whoever may open the admin shell may read what was addressed to them.
 */
final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(NotificationInbox::class, static fn (Application $app): NotificationInbox => new NotificationInbox(
            $app->make(DatabaseManager::class)->connection(),
        ));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        if ($this->app->runningInConsole()) {
            $this->commands([PruneNotificationsCommand::class]);
        }
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command('larena:notifications:prune')->daily()->withoutOverlapping();
        });
    }
}
