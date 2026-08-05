<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'string', 'max:255', 'alpha_dash',
                Rule::unique('products')->where('tenant_id', $tenantId)->ignore($this->route('product')),
            ],
            'description' => ['nullable', 'string', 'max:20000'],
            'sku' => ['nullable', 'string', 'max:100'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'category_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
            'brand_id' => ['nullable', 'uuid', Rule::exists('brands', 'id')->where('tenant_id', $tenantId)],
            'attributes' => ['nullable', 'array'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['uuid', Rule::exists('tags', 'id')->where('tenant_id', $tenantId)],
        ];
    }
}
