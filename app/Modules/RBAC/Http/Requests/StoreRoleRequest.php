<?php

namespace App\Modules\RBAC\Http\Requests;

use App\Modules\RBAC\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Role::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'guard_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
