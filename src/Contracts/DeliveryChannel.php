<?php

declare(strict_types=1);

namespace Larena\Notification\Contracts;

use Larena\Notification\Enums\ChannelKind;

final readonly class DeliveryChannel
{
    public function __construct(
        public string $channelKey,
        public ChannelKind $kind,
        public bool $requiresQueue = false,
        public bool $requiresCapability = false,
        public ?string $secretRef = null,
    ) {
    }
}
