<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePointOfSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('point-of-sales.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->where('branch_id', $this->integer('branch_id')),
                Rule::unique('point_of_sales', 'warehouse_id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'users' => ['nullable', 'array'],
            'users.*' => [
                'integer',
                Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_id' => 'sucursal',
            'warehouse_id' => 'almacen vinculado',
            'name' => 'nombre',
            'users' => 'usuarios asignados',
            'users.*' => 'usuario asignado',
            'is_active' => 'estado',
        ];
    }
}
