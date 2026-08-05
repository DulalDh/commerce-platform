<?php

namespace App\Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $productRule = ['required', 'uuid', Rule::exists('products', 'id')->where('tenant_id', $tenantId)];
        $variantRule = ['nullable', 'uuid', Rule::exists('product_variants', 'id')->where('tenant_id', $tenantId)];

        return [
            'from_product_id' => $productRule,
            'from_variant_id' => $variantRule,
            'to_product_id' => $productRule,
            'to_variant_id' => $variantRule,
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
