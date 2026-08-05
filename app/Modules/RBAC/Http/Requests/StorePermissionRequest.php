<?php

namespace App\Modules\RBAC\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenant_id === null && $this->user()->hasRole('super-admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'guard_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
