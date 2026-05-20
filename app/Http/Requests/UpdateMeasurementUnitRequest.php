<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeasurementUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('measurement-units.update') ?? false;
    }

    public function rules(): array
    {
        $unitId = $this->route('measurement_unit')?->id ?? $this->route('measurement_unit');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('measurement_units', 'name')->ignore($unitId)],
            'abbreviation' => ['required', 'string', 'max:20', Rule::unique('measurement_units', 'abbreviation')->ignore($unitId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
