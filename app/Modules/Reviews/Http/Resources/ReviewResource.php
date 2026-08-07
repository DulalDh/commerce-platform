<?php

namespace App\Modules\Reviews\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class ReviewResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'quality_rating' => $this->quality_rating,
            'behavior_rating' => $this->behavior_rating,
            'time_rating' => $this->time_rating,
            'communication_rating' => $this->communication_rating,
            'overall_rating' => $this->overall_rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
        ];
    }
}
