<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\ReminderLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [1, 5, 15, 30, 60];

    public function __construct(
        public int $reminderId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $reminder = Reminder::query()->findOrFail($this->reminderId);

        ReminderLog::query()->create([
            'reminder_id' => $reminder->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
            'provider_response' => 'queued_stub',
            'attempted_at' => now(),
        ]);

        $reminder->update([
            'status' => ReminderStatus::Sent,
            'sent_at' => now(),
        ]);
    }
}
