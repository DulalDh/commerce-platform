<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'product_id' => ['required', 'uuid', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'variant_id' => ['nullable', 'uuid', Rule::exists('product_variants', 'id')->where('tenant_id', $tenantId)],
            'delta' => ['required', 'integer', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
