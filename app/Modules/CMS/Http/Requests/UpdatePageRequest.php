<?php

namespace App\Modules\CMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'string', 'max:255', 'alpha_dash',
                Rule::unique('pages')->where('tenant_id', $tenantId)->ignore($this->route('page')),
            ],
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', Rule::in(['draft', 'published'])],
        ];
    }
}
