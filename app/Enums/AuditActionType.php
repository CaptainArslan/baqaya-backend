<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditActionType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Corrected = 'corrected';
    case Reversed = 'reversed';
    case SyncPush = 'sync_push';
    case SyncPull = 'sync_pull';
    case Payment = 'payment';
    case StatementGenerated = 'statement_generated';
    case ReminderSent = 'reminder_sent';
}
