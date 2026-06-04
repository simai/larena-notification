<?php

declare(strict_types=1);

namespace Larena\Notification\Contracts;

use Larena\Notification\Enums\NotificationSeverity;

final readonly class NotificationAdminItem
{
    public function __construct(
        public string $itemRef,
        public string $sourcePackage,
        public NotificationSeverity $severity,
        public string $title,
        public string $recommendedFix,
        public ?string $auditCorrelationRef = null,
    ) {
    }
}
