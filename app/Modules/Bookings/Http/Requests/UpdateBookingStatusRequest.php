<?php

namespace App\Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['on_the_way', 'started', 'completed', 'cancelled', 'refunded'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
