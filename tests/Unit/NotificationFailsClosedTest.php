<?php

declare(strict_types=1);

use Larena\Notification\Contracts\DeliveryAttempt;
use Larena\Notification\Contracts\NotificationEventDescriptor;
use Larena\Notification\Contracts\RecipientPreference;
use Larena\Notification\Contracts\RecipientRef;
use Larena\Notification\Enums\DeliveryAttemptState;
use Larena\Notification\Enums\NotificationSeverity;
use Larena\Notification\Enums\PreferenceDecision;

$securityEvent = new NotificationEventDescriptor(
    eventKey: 'security.login.blocked',
    severity: NotificationSeverity::Security,
    audiencePolicy: 'security_admin',
    payloadSchema: ['actor_ref' => 'string'],
    defaultChannels: ['admin'],
    mandatory: true,
);

assert($securityEvent->mandatory === true);

$preference = new RecipientPreference(
    recipient: new RecipientRef(actorRef: 'user:security'),
    eventKey: $securityEvent->eventKey,
    channelKey: 'admin',
    decision: PreferenceDecision::Mandatory,
    reason: 'security_notification',
);

assert($preference->decision === PreferenceDecision::Mandatory);

$attempt = new DeliveryAttempt(
    attemptRef: 'notification:attempt:1',
    eventKey: $securityEvent->eventKey,
    recipient: $preference->recipient,
    channelKey: 'admin',
    state: DeliveryAttemptState::Failed,
    attemptNumber: 1,
    safeDiagnostic: 'provider_unavailable',
);

assert($attempt->canRetry(3) === true);
