<?php

namespace App\Modules\Subscriptions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id !== null;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'uuid', Rule::exists('subscription_plans', 'id')],
        ];
    }
}
