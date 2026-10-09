<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', 'nullable', Rule::in(['ingreso', 'egreso'])],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in' => 'El tipo debe ser ingreso o egreso.',
        ];
    }
}
