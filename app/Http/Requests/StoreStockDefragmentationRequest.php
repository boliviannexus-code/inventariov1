<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockDefragmentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.movements') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'presentation_id' => ['required', 'exists:presentations,id'],
            'package_quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
