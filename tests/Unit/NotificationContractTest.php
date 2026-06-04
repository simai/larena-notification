<?php

declare(strict_types=1);

use Larena\Notification\Contracts\NotificationEventDescriptor;
use Larena\Notification\Contracts\RecipientRef;
use Larena\Notification\Contracts\TemplateDescriptor;
use Larena\Notification\Enums\NotificationSeverity;

$event = new NotificationEventDescriptor(
    eventKey: 'workflow.item.assigned',
    severity: NotificationSeverity::Info,
    audiencePolicy: 'assignee',
    payloadSchema: ['work_item_ref' => 'string'],
    defaultChannels: ['admin', 'email'],
);

assert($event->payloadSchema['work_item_ref'] === 'string');
assert($event->mandatory === false);

$recipient = new RecipientRef(actorRef: 'user:1', locale: 'en');
$template = new TemplateDescriptor(
    templateKey: 'workflow.item.assigned',
    locale: $recipient->locale ?? 'en',
    subjectKey: 'notification.workflow.assigned.subject',
    bodyKey: 'notification.workflow.assigned.body',
    requiredContextKeys: ['work_item_ref'],
);

assert($template->requiredContextKeys === ['work_item_ref']);
