<?php

namespace App\Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['product', 'service'])],
            'tenant_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
