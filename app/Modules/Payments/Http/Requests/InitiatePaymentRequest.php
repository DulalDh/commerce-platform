<?php

namespace App\Modules\Payments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'uuid', Rule::exists('orders', 'id')->where('tenant_id', $this->user()->tenant_id)],
            'gateway' => ['required', Rule::in([
                'sslcommerz', 'bkash', 'cod', 'stripe', 'paypal', 'nagad', 'rocket', 'bank_transfer',
            ])],
        ];
    }
}
