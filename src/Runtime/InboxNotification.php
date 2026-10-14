<?php

declare(strict_types=1);

namespace Larena\Notification\Runtime;

/** One stored notification as its recipient sees it. Times are UTC "Y-m-d H:i:s". */
final readonly class InboxNotification
{
    public function __construct(
        public string $id,
        public string $recipientRef,
        public string $status,
        public string $title,
        public ?string $text,
        public string $source,
        public ?string $href,
        public int $repeatCount,
        public string $createdAt,
        public ?string $shownAt,
        public ?string $readAt,
    ) {
    }

    public function unread(): bool
    {
        return $this->readAt === null;
    }
}
