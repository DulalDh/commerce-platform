<?php

namespace App\Modules\Notifications\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class NotificationTemplateResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'body' => $this->body,
            'is_active' => $this->is_active,
        ];
    }
}
