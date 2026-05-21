<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pos.access') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_document_number' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'payment_mode' => ['nullable', 'in:cash,mixed'],
            'cash_payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_id' => ['required_with:payments', 'exists:payment_methods,id'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.presentation_id' => ['required', 'exists:presentations,id'],
            'items.*.package_quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'customer_document_number' => 'documento del cliente',
            'customer_name' => 'nombre del cliente',
            'payment_mode' => 'modo de pago',
            'cash_payment_method_id' => 'metodo efectivo',
            'cash_received' => 'monto recibido',
            'payments' => 'pagos',
            'payments.*.payment_method_id' => 'metodo de pago',
            'payments.*.amount' => 'monto del pago',
            'payments.*.reference' => 'referencia del pago',
            'items' => 'productos',
            'items.*.product_id' => 'producto',
            'items.*.presentation_id' => 'presentacion',
            'items.*.package_quantity' => 'cantidad',
            'items.*.unit_price' => 'precio',
            'items.*.discount' => 'descuento',
        ];
    }
}
