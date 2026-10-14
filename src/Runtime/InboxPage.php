<?php

declare(strict_types=1);

namespace Larena\Notification\Runtime;

/** A page of a recipient's notifications, newest first, and how many match the filter in all. */
final readonly class InboxPage
{
    /** @param list<InboxNotification> $items */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
