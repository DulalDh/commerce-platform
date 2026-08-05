<?php

namespace App\Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                'confirmed', 'processing', 'shipped', 'delivered',
                'cancelled', 'returned', 'refunded', 'exchanged',
            ])],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
