<?php

namespace App\Modules\Subscriptions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:subscription_plans,slug'],
            'price' => ['required', 'numeric', 'min:0'],
            'limits' => ['nullable', 'array'],
            'limits.products' => ['nullable', 'integer', 'min:0'],
            'limits.staff' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
