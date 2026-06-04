<?php

declare(strict_types=1);

namespace Larena\Notification\Enums;

enum PreferenceDecision: string
{
    case Allowed = 'allowed';
    case Suppressed = 'suppressed';
    case Mandatory = 'mandatory';
}
