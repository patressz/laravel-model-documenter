<?php

declare(strict_types=1);

namespace Workbench\App\Enums;

enum NotificationChannel: string
{
    case Email = 'email';
    case PushNotification = 'push_notification';
}
