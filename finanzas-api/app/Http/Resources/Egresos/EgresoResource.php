<?php

namespace App\Http\Resources\Egresos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EgresoResource extends JsonResource
{
    /**
     * Transforma el egreso en la respuesta JSON de la API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha?->toDateString(),
            'descripcion' => $this->descripcion,
            'monto' => $this->monto,
            'notas' => $this->notas,
            'categoria_id' => $this->categoria_id,
            'subcategoria_id' => $this->subcategoria_id,
            'categoria' => CategoriaResource::make($this->whenLoaded('categoria')),
            'subcategoria' => SubcategoriaResource::make($this->whenLoaded('subcategoria')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
