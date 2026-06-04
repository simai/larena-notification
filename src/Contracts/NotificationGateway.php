<?php

declare(strict_types=1);

namespace Larena\Notification\Contracts;

interface NotificationGateway
{
    public function describeEvent(string $eventKey): ?NotificationEventDescriptor;

    public function resolvePreference(RecipientRef $recipient, string $eventKey, string $channelKey): RecipientPreference;

    public function createAttempt(NotificationMessage $message, DeliveryChannel $channel): DeliveryAttempt;
}
