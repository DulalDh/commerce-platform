<?php

namespace App\Modules\Bookings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'staff_id' => ['required', 'uuid', Rule::exists('staff', 'id')->where('tenant_id', $this->user()->tenant_id)],
        ];
    }
}
