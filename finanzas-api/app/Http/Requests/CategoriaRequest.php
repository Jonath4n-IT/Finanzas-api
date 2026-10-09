<?php

namespace App\Http\Requests;

use App\Models\Categoria;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if (($this->isMethod('PUT') || $this->isMethod('PATCH'))
            && ! $user->categorias()->whereKey($this->route('categoria'))->exists()) {
            abort(404);
        }

        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->getAuthIdentifier();
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';
        $tipo = $this->input('tipo');
        $categoriaId = $this->route('categoria');

        if ($this->isMethod('PATCH') && ! $this->exists('tipo')) {
            $tipo = Categoria::query()
                ->where('user_id', $userId)
                ->whereKey($categoriaId)
                ->value('tipo');
        }

        $uniqueNombre = Rule::unique('categorias', 'nombre')->where(
            fn (Builder $query) => $query->where('user_id', $userId)->where('tipo', $tipo)
        );

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $uniqueNombre->ignore($categoriaId);
        }

        return [
            'nombre' => [$required, 'string', 'max:80', $uniqueNombre],
            'tipo' => [$required, Rule::in(['ingreso', 'egreso'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.string' => 'El nombre de la categoría debe ser texto.',
            'nombre.max' => 'El nombre de la categoría no puede exceder 80 caracteres.',
            'nombre.unique' => 'Ya tienes una categoría con ese nombre y tipo.',
            'tipo.required' => 'El tipo de categoría es obligatorio.',
            'tipo.in' => 'El tipo debe ser ingreso o egreso.',
        ];
    }
}
