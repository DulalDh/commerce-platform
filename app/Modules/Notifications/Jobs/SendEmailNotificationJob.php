<?php

namespace App\Modules\Notifications\Jobs;

use App\Modules\Notifications\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Stub: logs instead of sending via SMTP. A real integration would use
 * Laravel's Mail facade with config('mail') (already SMTP-configured in
 * Phase 0) - this job's shape (load notification, attempt delivery, mark
 * sent/failed) stays the same either way.
 */
class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $notificationId) {}

    public function handle(): void
    {
        $notification = Notification::find($this->notificationId);

        if (! $notification) {
            return;
        }

        Log::info('Email notification sent (stub)', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'subject' => $notification->subject,
        ]);

        $notification->update(['status' => 'sent', 'sent_at' => now()]);
    }
}
