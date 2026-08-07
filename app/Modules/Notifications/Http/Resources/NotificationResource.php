<?php

namespace App\Modules\Notifications\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class NotificationResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'channel' => $this->channel,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
