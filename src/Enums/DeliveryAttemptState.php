<?php

declare(strict_types=1);

namespace Larena\Notification\Enums;

enum DeliveryAttemptState: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Suppressed = 'suppressed';
}
