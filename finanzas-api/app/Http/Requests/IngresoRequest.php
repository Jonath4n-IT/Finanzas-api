<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if (($this->isMethod('PUT') || $this->isMethod('PATCH'))
            && ! $user->ingresos()->whereKey($this->route('ingreso'))->exists()) {
            abort(404);
        }

        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->getAuthIdentifier();
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'fecha' => [$required, 'date', 'before_or_equal:today'],
            'fuente' => [$required, 'string', 'max:150'],
            'categoria_id' => [
                $required,
                'integer',
                Rule::exists('categorias', 'id')->where('tipo', 'ingreso')->where(
                    fn (Builder $query) => $query->whereNull('user_id')
                        ->orWhere('user_id', $userId)
                ),
            ],
            'monto' => [$required, 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
            'notas' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'fuente.required' => 'La fuente del ingreso es obligatoria.',
            'fuente.string' => 'La fuente debe ser texto.',
            'fuente.max' => 'La fuente no puede exceder 150 caracteres.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.integer' => 'La categoría seleccionada no es válida.',
            'categoria_id.exists' => 'La categoría debe existir, ser de tipo ingreso y pertenecer a tu cuenta o al sistema.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser numérico.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.decimal' => 'El monto debe tener como máximo dos decimales.',
            'monto.max' => 'El monto no puede exceder 9,999,999,999.99.',
            'notas.string' => 'Las notas deben ser texto.',
        ];
    }
}
