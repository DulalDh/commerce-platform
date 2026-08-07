<?php

namespace App\Modules\Services\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'years_experience' => ['nullable', 'integer', 'min:0'],
            'service_area' => ['nullable', 'array'],
            'service_area.cities' => ['nullable', 'array'],
            'service_area.radius_km' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
