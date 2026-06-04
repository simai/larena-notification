<?php

declare(strict_types=1);

namespace Larena\Notification\Contracts;

use Larena\Notification\Enums\PreferenceDecision;

final readonly class RecipientPreference
{
    public function __construct(
        public RecipientRef $recipient,
        public string $eventKey,
        public string $channelKey,
        public PreferenceDecision $decision,
        public string $reason = 'user_preference',
    ) {
    }
}
