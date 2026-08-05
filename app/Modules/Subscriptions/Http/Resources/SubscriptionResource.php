<?php

namespace App\Modules\Subscriptions\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class SubscriptionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_plan_id' => $this->subscription_plan_id,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'current_period_end' => $this->current_period_end,
            'plan' => new PlanResource($this->whenLoaded('plan')),
        ];
    }
}
