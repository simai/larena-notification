<?php

declare(strict_types=1);

namespace Larena\Notification\Contracts;

final readonly class NotificationMessage
{
    /**
     * @param array<string, mixed> $safeContext
     */
    public function __construct(
        public string $eventKey,
        public RecipientRef $recipient,
        public TemplateDescriptor $template,
        public array $safeContext = [],
    ) {
    }
}
