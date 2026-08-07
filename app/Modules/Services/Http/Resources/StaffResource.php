<?php

namespace App\Modules\Services\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class StaffResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_active' => $this->is_active,
        ];
    }
}
