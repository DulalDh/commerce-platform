<?php

namespace App\Modules\Services\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'staff_id' => ['nullable', 'uuid', Rule::exists('staff', 'id')->where('tenant_id', $tenantId)],
            'type' => ['required', 'string', 'max:100'],
            'registration_number' => [
                'required', 'string', 'max:100',
                Rule::unique('vehicles')->where('tenant_id', $tenantId),
            ],
            'capacity_kg' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
