<?php

declare(strict_types=1);

namespace Larena\Notification\Tests\Unit;

use Larena\Notification\Contracts\NotificationEventDescriptor;
use Larena\Notification\Contracts\RecipientRef;
use Larena\Notification\Contracts\TemplateDescriptor;
use Larena\Notification\Enums\NotificationSeverity;
use PHPUnit\Framework\TestCase;

final class NotificationContractTest extends TestCase
{
    public function test_an_event_keeps_its_payload_schema_and_is_optional_by_default(): void
    {
        $event = new NotificationEventDescriptor(
            eventKey: 'workflow.item.assigned',
            severity: NotificationSeverity::Info,
            audiencePolicy: 'assignee',
            payloadSchema: ['work_item_ref' => 'string'],
            defaultChannels: ['admin', 'email'],
        );

        self::assertSame('string', $event->payloadSchema['work_item_ref']);
        self::assertFalse($event->mandatory);
    }

    public function test_a_template_takes_the_recipients_locale_and_names_its_required_context(): void
    {
        $recipient = new RecipientRef(actorRef: 'user:1', locale: 'en');
        $template = new TemplateDescriptor(
            templateKey: 'workflow.item.assigned',
            locale: $recipient->locale ?? 'en',
            subjectKey: 'notification.workflow.assigned.subject',
            bodyKey: 'notification.workflow.assigned.body',
            requiredContextKeys: ['work_item_ref'],
        );

        self::assertSame('en', $template->locale);
        self::assertSame(['work_item_ref'], $template->requiredContextKeys);
    }
}
