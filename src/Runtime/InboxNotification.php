<?php

declare(strict_types=1);

namespace Larena\Notification\Runtime;

/**
 * One stored notification as its recipient sees it. Times are UTC "Y-m-d H:i:s". `object` is what it is about (a site,
 * «Сервер»); a resolved one carries when and how it ended (`resolution`), in the same record as the problem.
 */
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
        public ?string $object = null,
        public ?string $resolvedAt = null,
        public ?string $resolution = null,
    ) {
    }

    public function unread(): bool
    {
        return $this->readAt === null;
    }

    public function resolved(): bool
    {
        return $this->resolvedAt !== null;
    }
}
