<?php

namespace App\Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'uuid', Rule::exists('services', 'id')],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_slot_start' => ['required', 'date_format:H:i'],
            'scheduled_slot_end' => ['required', 'date_format:H:i', 'after:scheduled_slot_start'],
            'address' => ['required', 'array'],
            'address.line1' => ['required', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:120'],
            'is_emergency' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
