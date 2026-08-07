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
 * Stub: logs instead of calling an SMS gateway (Twilio, etc). Swap the body
 * of handle() for a real HTTP call when a gateway is chosen - the
 * notification lifecycle (pending -> sent/failed) doesn't change.
 */
class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $notificationId) {}

    public function handle(): void
    {
        $notification = Notification::find($this->notificationId);

        if (! $notification) {
            return;
        }

        Log::info('SMS notification sent (stub)', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'body' => $notification->body,
        ]);

        $notification->update(['status' => 'sent', 'sent_at' => now()]);
    }
}
