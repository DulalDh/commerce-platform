<?php

namespace App\Modules\Subscriptions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash', Rule::unique('subscription_plans', 'slug')->ignore($this->route('plan'))],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'limits' => ['nullable', 'array'],
            'limits.products' => ['nullable', 'integer', 'min:0'],
            'limits.staff' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
