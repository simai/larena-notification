<?php

declare(strict_types=1);

namespace Larena\Notification\Enums;

enum ChannelKind: string
{
    case Admin = 'admin';
    case Email = 'email';
    case Webhook = 'webhook';
    case ExternalProvider = 'external_provider';
}
