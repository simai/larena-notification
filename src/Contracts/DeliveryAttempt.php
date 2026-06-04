<?php

declare(strict_types=1);

namespace Larena\Notification\Contracts;

use Larena\Notification\Enums\DeliveryAttemptState;

final readonly class DeliveryAttempt
{
    public function __construct(
        public string $attemptRef,
        public string $eventKey,
        public RecipientRef $recipient,
        public string $channelKey,
        public DeliveryAttemptState $state = DeliveryAttemptState::Pending,
        public int $attemptNumber = 0,
        public ?string $safeDiagnostic = null,
    ) {
    }

    public function canRetry(int $maxAttempts): bool
    {
        return $this->state === DeliveryAttemptState::Failed && $this->attemptNumber < $maxAttempts;
    }
}
