<?php

declare(strict_types=1);

namespace Larena\Notification\Tests\Unit;

use Larena\Notification\Contracts\DeliveryAttempt;
use Larena\Notification\Contracts\NotificationEventDescriptor;
use Larena\Notification\Contracts\RecipientPreference;
use Larena\Notification\Contracts\RecipientRef;
use Larena\Notification\Enums\DeliveryAttemptState;
use Larena\Notification\Enums\NotificationSeverity;
use Larena\Notification\Enums\PreferenceDecision;
use PHPUnit\Framework\TestCase;

final class NotificationFailsClosedTest extends TestCase
{
    public function test_a_security_event_is_mandatory_and_its_failed_delivery_may_be_retried(): void
    {
        $securityEvent = new NotificationEventDescriptor(
            eventKey: 'security.login.blocked',
            severity: NotificationSeverity::Security,
            audiencePolicy: 'security_admin',
            payloadSchema: ['actor_ref' => 'string'],
            defaultChannels: ['admin'],
            mandatory: true,
        );
        self::assertTrue($securityEvent->mandatory);

        $preference = new RecipientPreference(
            recipient: new RecipientRef(actorRef: 'user:security'),
            eventKey: $securityEvent->eventKey,
            channelKey: 'admin',
            decision: PreferenceDecision::Mandatory,
            reason: 'security_notification',
        );
        self::assertSame(PreferenceDecision::Mandatory, $preference->decision);

        $attempt = new DeliveryAttempt(
            attemptRef: 'notification:attempt:1',
            eventKey: $securityEvent->eventKey,
            recipient: $preference->recipient,
            channelKey: 'admin',
            state: DeliveryAttemptState::Failed,
            attemptNumber: 1,
            safeDiagnostic: 'provider_unavailable',
        );
        self::assertTrue($attempt->canRetry(3));
        self::assertFalse($attempt->canRetry(1), 'An attempt that used its last try is not retried.');
    }
}
