<?php

namespace App\Modules\Tenants\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:merchant,service_provider,both'],
            'subdomain' => ['required', 'string', 'max:63', 'alpha_dash', 'unique:tenants,subdomain'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:tenants,domain'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
        ];
    }
}
