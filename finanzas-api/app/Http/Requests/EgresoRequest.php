<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EgresoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if (($this->isMethod('PUT') || $this->isMethod('PATCH'))
            && ! $user->egresos()->whereKey($this->route('egreso'))->exists()) {
            abort(404);
        }

        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->getAuthIdentifier();
        $isPartialUpdate = $this->isMethod('PATCH');
        $required = $isPartialUpdate ? 'sometimes' : 'required';
        $categoriaId = $this->input('categoria_id');

        if ($isPartialUpdate && ! $this->exists('categoria_id') && $this->exists('subcategoria_id')) {
            $categoriaId = $this->user()
                ->egresos()
                ->whereKey($this->route('egreso'))
                ->value('categoria_id');
        }

        return [
            'fecha' => [$required, 'date', 'before_or_equal:today'],
            'descripcion' => [$required, 'string', 'max:150'],
            'categoria_id' => [
                $required,
                'integer',
                Rule::exists('categorias', 'id')->where('tipo', 'egreso')->where(
                    fn (Builder $query) => $query->whereNull('user_id')
                        ->orWhere('user_id', $userId)
                ),
            ],
            'subcategoria_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('subcategorias', 'id')
                    ->where('categoria_id', $categoriaId)
                    ->where(function (Builder $query) use ($userId): void {
                        $query->whereExists(function (Builder $query) use ($userId): void {
                            $query->selectRaw('1')
                                ->from('categorias')
                                ->whereColumn('categorias.id', 'subcategorias.categoria_id')
                                ->where('categorias.tipo', 'egreso')
                                ->where(fn (Builder $query) => $query->whereNull('categorias.user_id')
                                    ->orWhere('categorias.user_id', $userId));
                        });
                    }),
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
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no puede exceder 150 caracteres.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.integer' => 'La categoría seleccionada no es válida.',
            'categoria_id.exists' => 'La categoría debe existir, ser de tipo egreso y pertenecer a tu cuenta o al sistema.',
            'subcategoria_id.integer' => 'La subcategoría seleccionada no es válida.',
            'subcategoria_id.exists' => 'La subcategoría debe pertenecer a una categoría de egreso permitida.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser numérico.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'monto.decimal' => 'El monto debe tener como máximo dos decimales.',
            'monto.max' => 'El monto no puede exceder 9,999,999,999.99.',
            'notas.string' => 'Las notas deben ser texto.',
        ];
    }
}
