<?php

namespace App\Modules\Promotions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFlashSaleRequest extends FormRequest
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
            'discount_percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['uuid', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
        ];
    }
}
