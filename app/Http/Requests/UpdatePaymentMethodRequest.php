<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payment-methods.update') ?? false;
    }

    public function rules(): array
    {
        $paymentMethodId = $this->route('payment_method')?->id ?? $this->route('payment_method');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('payment_methods', 'name')->ignore($paymentMethodId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
