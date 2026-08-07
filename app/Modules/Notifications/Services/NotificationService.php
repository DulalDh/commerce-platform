<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Jobs\SendEmailNotificationJob;
use App\Modules\Notifications\Jobs\SendPushNotificationJob;
use App\Modules\Notifications\Jobs\SendSmsNotificationJob;
use App\Modules\Notifications\Jobs\SendWhatsAppNotificationJob;
use App\Modules\Notifications\Models\Notification;
use InvalidArgumentException;

/**
 * Single entry point for every notification in the system - "unified" in
 * the sense that every other module calls send() with an event type and
 * doesn't know or care which channels are actually configured to fire for
 * that event.
 */
class NotificationService
{
    /** @var array<string, class-string> */
    protected const CHANNEL_JOBS = [
        'email' => SendEmailNotificationJob::class,
        'sms' => SendSmsNotificationJob::class,
        'whatsapp' => SendWhatsAppNotificationJob::class,
        'push' => SendPushNotificationJob::class,
    ];

    public function __construct(protected TemplateRenderer $renderer) {}

    /**
     * @param  string[]  $channels
     */
    public function send(int $userId, string $eventType, array $data = [], array $channels = ['in_app']): void
    {
        foreach ($channels as $channel) {
            $this->sendToChannel($userId, $eventType, $channel, $data);
        }
    }

    protected function sendToChannel(int $userId, string $eventType, string $channel, array $data): void
    {
        $rendered = $this->renderer->render($eventType, $channel, $data);

        $notification = Notification::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'channel' => $channel,
            'subject' => $rendered['subject'],
            'body' => $rendered['body'],
            'status' => $channel === 'in_app' ? 'sent' : 'pending',
            'sent_at' => $channel === 'in_app' ? now() : null,
        ]);

        if ($channel === 'in_app') {
            return;
        }

        $jobClass = self::CHANNEL_JOBS[$channel] ?? null;

        if (! $jobClass) {
            throw new InvalidArgumentException("Unknown notification channel [{$channel}].");
        }

        $jobClass::dispatch((string) $notification->id);
    }
}
