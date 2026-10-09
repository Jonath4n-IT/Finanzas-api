<?php

namespace App\Http\Requests;

use App\Models\Subcategoria;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubcategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $perteneceAlUsuario = Subcategoria::query()
                ->whereKey($this->route('subcategoria'))
                ->whereHas('categoria', fn (Builder $query) => $query
                    ->where('user_id', $user->getAuthIdentifier())
                    ->where('tipo', 'egreso'))
                ->exists();

            if (! $perteneceAlUsuario) {
                abort(404);
            }
        }

        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->getAuthIdentifier();
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';
        $categoriaId = $this->input('categoria_id');
        $subcategoriaId = $this->route('subcategoria');

        if ($this->isMethod('PATCH') && ! $this->exists('categoria_id')) {
            $categoriaId = Subcategoria::query()
                ->whereKey($subcategoriaId)
                ->whereHas('categoria', fn (Builder $query) => $query
                    ->where('user_id', $userId)
                    ->where('tipo', 'egreso'))
                ->value('categoria_id');
        }

        $uniqueNombre = Rule::unique('subcategorias', 'nombre')
            ->where(fn (Builder $query) => $query->where('categoria_id', $categoriaId));

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $uniqueNombre->ignore($subcategoriaId);
        }

        return [
            'categoria_id' => [
                $required,
                'integer',
                Rule::exists('categorias', 'id')->where('tipo', 'egreso')->where(
                    fn (Builder $query) => $query->where('user_id', $userId)
                ),
            ],
            'nombre' => [$required, 'string', 'max:80', $uniqueNombre],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.integer' => 'La categoría seleccionada no es válida.',
            'categoria_id.exists' => 'Solo puedes administrar subcategorías de tus categorías de egreso.',
            'nombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'nombre.string' => 'El nombre de la subcategoría debe ser texto.',
            'nombre.max' => 'El nombre de la subcategoría no puede exceder 80 caracteres.',
            'nombre.unique' => 'Ya existe una subcategoría con ese nombre en la categoría seleccionada.',
        ];
    }
}
