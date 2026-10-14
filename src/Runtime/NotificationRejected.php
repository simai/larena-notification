<?php

declare(strict_types=1);

namespace Larena\Notification\Runtime;

use InvalidArgumentException;

/** A notification the inbox refuses to store; the message is a stable reason code. */
final class NotificationRejected extends InvalidArgumentException
{
}
