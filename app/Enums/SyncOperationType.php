<?php

declare(strict_types=1);

namespace App\Enums;

enum SyncOperationType: string
{
    case CustomerCreate = 'customer.create';
    case CustomerUpdate = 'customer.update';
    case CustomerDelete = 'customer.delete';
    case TransactionCreate = 'transaction.create';
    case TransactionCorrect = 'transaction.correct';
    case TransactionReverse = 'transaction.reverse';
    case TransactionMetadataUpdate = 'transaction.metadata_update';
    case PaymentCreate = 'payment.create';
    case PaymentCorrect = 'payment.correct';
    case PaymentReverse = 'payment.reverse';
    case PaymentMetadataUpdate = 'payment.metadata_update';
    case ReminderCreate = 'reminder.create';
}
