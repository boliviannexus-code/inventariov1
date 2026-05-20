<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.presentation_id' => ['required', 'exists:presentations,id'],
            'items.*.package_quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'proveedor',
            'warehouse_id' => 'almacen destino',
            'purchase_date' => 'fecha de compra',
            'items' => 'detalle de productos',
            'items.*.product_id' => 'producto',
            'items.*.presentation_id' => 'presentacion',
            'items.*.package_quantity' => 'cantidad',
            'items.*.unit_price' => 'precio unitario',
        ];
    }
}
