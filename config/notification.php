<?php

declare(strict_types=1);

return [
    // The daily removal of notifications older than 30 days (larena:notifications:prune) on the application's scheduler
    // (`schedule:run`). Off unless the application turns it on: the panel's edition does, another application may by
    // its own config/larena-notification.php or LARENA_NOTIFICATION_SCHEDULER_ENABLED=true.
    'scheduler' => [
        'enabled' => filter_var(getenv('LARENA_NOTIFICATION_SCHEDULER_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL),
    ],
];
