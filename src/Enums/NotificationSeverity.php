<?php

declare(strict_types=1);

namespace Larena\Notification\Enums;

enum NotificationSeverity: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Critical = 'critical';
    case Security = 'security';
}
