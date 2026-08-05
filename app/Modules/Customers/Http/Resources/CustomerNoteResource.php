<?php

namespace App\Modules\Customers\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CustomerNoteResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
