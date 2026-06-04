<?php

declare(strict_types=1);

namespace Larena\Notification\Contracts;

final readonly class RecipientRef
{
    public function __construct(
        public string $actorRef,
        public ?string $addressRef = null,
        public ?string $locale = null,
    ) {
    }
}
