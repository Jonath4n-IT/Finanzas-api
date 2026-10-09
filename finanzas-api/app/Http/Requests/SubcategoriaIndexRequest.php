<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubcategoriaIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()?->getAuthIdentifier();

        return [
            'categoria_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('categorias', 'id')->where('tipo', 'egreso')->where(
                    fn (Builder $query) => $query->whereNull('user_id')
                        ->orWhere('user_id', $userId)
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.integer' => 'La categoría seleccionada no es válida.',
            'categoria_id.exists' => 'La categoría debe ser de egreso y pertenecer a tu cuenta o al sistema.',
        ];
    }
}
