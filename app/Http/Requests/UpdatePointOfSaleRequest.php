<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\CompanyContext;

class UpdatePointOfSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('point-of-sales.update') ?? false) && CompanyContext::canOperate($this->user());
    }

    public function rules(): array
    {
        $pointOfSaleId = $this->route('point_of_sale')?->id ?? $this->route('point_of_sale');
        $branchRule = Rule::exists('branches', 'id')->whereNull('deleted_at');
        $warehouseRule = Rule::exists('warehouses', 'id')
            ->where('branch_id', $this->integer('branch_id'))
            ->whereNull('deleted_at');
        $userRule = Rule::exists('users', 'id')
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($companyId = CompanyContext::id($this->user())) {
            $branchRule->where('company_id', $companyId);
            $warehouseRule->where('company_id', $companyId);
            $userRule->where('company_id', $companyId);
        }

        return [
            'branch_id' => [
                'required',
                $branchRule,
            ],
            'warehouse_id' => [
                'required',
                $warehouseRule,
                Rule::unique('point_of_sales', 'warehouse_id')->ignore($pointOfSaleId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'users' => ['nullable', 'array'],
            'users.*' => [
                'integer',
                $userRule,
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
