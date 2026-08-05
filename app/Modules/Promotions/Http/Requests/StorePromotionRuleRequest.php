<?php

namespace App\Modules\Promotions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $productExists = Rule::exists('products', 'id')->where('tenant_id', $tenantId);

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['bundle', 'bogo'])],
            'is_active' => ['sometimes', 'boolean'],

            'config.product_ids' => ['required_if:type,bundle', 'array', 'min:2'],
            'config.product_ids.*' => ['uuid', $productExists],
            'config.bundle_price' => ['required_if:type,bundle', 'numeric', 'min:0'],

            'config.buy_product_id' => ['required_if:type,bogo', 'uuid', $productExists],
            'config.get_product_id' => ['required_if:type,bogo', 'uuid', $productExists],
            'config.buy_qty' => ['required_if:type,bogo', 'integer', 'min:1'],
            'config.get_qty' => ['required_if:type,bogo', 'integer', 'min:1'],
            'config.get_discount_percentage' => ['required_if:type,bogo', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
