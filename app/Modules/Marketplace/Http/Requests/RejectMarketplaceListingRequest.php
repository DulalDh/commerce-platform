<?php

namespace App\Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectMarketplaceListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
