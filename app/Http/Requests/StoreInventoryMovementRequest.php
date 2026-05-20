<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.movements') ?? false;
    }

    public function rules(): array
    {
        return [
            'operation' => ['required', Rule::in(['in', 'out', 'transfer'])],
            'warehouse_id' => ['required_if:operation,in,out', 'nullable', 'exists:warehouses,id'],
            'source_warehouse_id' => ['required_if:operation,transfer', 'nullable', 'exists:warehouses,id'],
            'target_warehouse_id' => ['required_if:operation,transfer', 'nullable', 'exists:warehouses,id', 'different:source_warehouse_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.presentation_id' => ['nullable', 'exists:presentations,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'required_without:items.*.package_quantity'],
            'items.*.package_quantity' => ['nullable', 'integer', 'min:1', 'required_with:items.*.presentation_id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
