<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductPresentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('product-presentations.update') ?? false;
    }

    public function rules(): array
    {
        $presentationId = $this->route('product_presentation')?->id ?? $this->route('product_presentation');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('presentations', 'name')->ignore($presentationId)],
            'units_per_package' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
