<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeasurementUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('measurement-units.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:measurement_units,name'],
            'abbreviation' => ['required', 'string', 'max:20', 'unique:measurement_units,abbreviation'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
