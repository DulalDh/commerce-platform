<?php

namespace App\Modules\Services\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('services')->where('tenant_id', $tenantId),
            ],
            'description' => ['nullable', 'string', 'max:20000'],
            'service_category_id' => [
                'nullable', 'uuid', Rule::exists('service_categories', 'id')->where('tenant_id', $tenantId),
            ],
            'pricing_model' => ['required', Rule::in(['starting', 'hourly', 'fixed', 'emergency'])],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'service_area' => ['nullable', 'array'],
            'required_equipment' => ['nullable', 'array'],
            'cancellation_policy' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
