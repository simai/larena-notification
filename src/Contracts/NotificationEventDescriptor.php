<?php

declare(strict_types=1);

namespace Larena\Notification\Contracts;

use Larena\Notification\Enums\NotificationSeverity;

final readonly class NotificationEventDescriptor
{
    /**
     * @param array<string, string> $payloadSchema
     * @param list<string> $defaultChannels
     */
    public function __construct(
        public string $eventKey,
        public NotificationSeverity $severity,
        public string $audiencePolicy,
        public array $payloadSchema,
        public array $defaultChannels = [],
        public bool $mandatory = false,
    ) {
    }
}
