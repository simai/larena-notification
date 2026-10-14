<?php

declare(strict_types=1);

namespace Larena\Notification\Tests;

use Illuminate\Foundation\Application;
use Larena\Notification\Providers\NotificationServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'larena-notification-');
        if ($path === false) {
            self::fail('Could not allocate a temporary SQLite database.');
        }

        $this->databasePath = $path;
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'notification_testing', '--force' => true])
            ->assertSuccessful();
    }

    protected function tearDown(): void
    {
        $databasePath = $this->databasePath;
        parent::tearDown();

        if (is_file($databasePath) && !unlink($databasePath)) {
            self::fail("Could not remove temporary SQLite database: {$databasePath}");
        }
    }

    /** @param Application $app */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'notification_testing');
        $app['config']->set('database.connections.notification_testing', [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    /** @param Application $app
     *  @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [NotificationServiceProvider::class];
    }
}
