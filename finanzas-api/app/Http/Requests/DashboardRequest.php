<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'anio' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:9999'],
            'mes' => ['sometimes', 'nullable', 'integer', 'between:1,12'],
        ];
    }

    public function messages(): array
    {
        return [
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.min' => 'El año debe ser igual o posterior a 1900.',
            'anio.max' => 'El año no puede ser mayor que 9999.',
            'mes.integer' => 'El mes debe ser un número entero.',
            'mes.between' => 'El mes debe estar entre 1 y 12.',
        ];
    }
}
