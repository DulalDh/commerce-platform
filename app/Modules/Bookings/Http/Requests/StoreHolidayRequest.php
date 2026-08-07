<?php

namespace App\Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', Rule::unique('holidays')->where('tenant_id', $this->user()->tenant_id)],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
