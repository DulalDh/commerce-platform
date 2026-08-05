<?php

namespace App\Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(['pathao', 'steadfast', 'redx', 'sundarban'])],
        ];
    }
}
