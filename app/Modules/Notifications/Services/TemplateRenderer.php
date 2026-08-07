<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Models\NotificationTemplate;

class TemplateRenderer
{
    /**
     * @return array{subject: ?string, body: string}
     */
    public function render(string $eventType, string $channel, array $data): array
    {
        $template = NotificationTemplate::where('event_type', $eventType)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            // No template configured for this event+channel yet - fall back
            // to a generic message rather than silently dropping the
            // notification, so the system works out of the box before
            // anyone has authored templates.
            return [
                'subject' => null,
                'body' => "You have a new notification: {$eventType}.",
            ];
        }

        return [
            'subject' => $template->subject ? $this->interpolate($template->subject, $data) : null,
            'body' => $this->interpolate($template->body, $data),
        ];
    }

    protected function interpolate(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $text = str_replace("{{{$key}}}", (string) $value, $text);
            }
        }

        return $text;
    }
}
