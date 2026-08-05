<?php

namespace App\Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null || $this->user() !== null;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'product_id' => ['required', 'uuid', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'variant_id' => ['nullable', 'uuid', Rule::exists('product_variants', 'id')->where('tenant_id', $tenantId)],
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
