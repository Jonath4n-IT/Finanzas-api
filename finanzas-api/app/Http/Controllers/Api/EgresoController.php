<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EgresoIndexRequest;
use App\Http\Requests\EgresoRequest;
use App\Http\Resources\Egresos\EgresoCollection;
use App\Http\Resources\Egresos\EgresoResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EgresoController extends Controller
{
    public function index(EgresoIndexRequest $request): EgresoCollection
    {
        $filters = $request->validated();
        $user = $request->user();
        $query = $user
            ->egresos()
            ->with($this->relacionesVisiblesPara($user->getAuthIdentifier()))
            ->when(isset($filters['anio']), fn ($query) => $query->whereYear('fecha', $filters['anio']))
            ->when(isset($filters['mes']), fn ($query) => $query->whereMonth('fecha', $filters['mes']))
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        return EgresoCollection::make($query->paginate(15));
    }

    public function store(EgresoRequest $request): JsonResponse
    {
        $egreso = $request->user()->egresos()->create($request->validated());
        $egreso->load($this->relacionesVisiblesPara($request->user()->getAuthIdentifier()));

        return EgresoResource::make($egreso)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $egreso): EgresoResource
    {
        $registro = $request->user()
            ->egresos()
            ->with($this->relacionesVisiblesPara($request->user()->getAuthIdentifier()))
            ->findOrFail($egreso);

        return EgresoResource::make($registro);
    }

    public function update(EgresoRequest $request, int $egreso): EgresoResource
    {
        $registro = $request->user()->egresos()->findOrFail($egreso);
        $data = $request->validated();

        if (array_key_exists('categoria_id', $data)
            && (int) $data['categoria_id'] !== (int) $registro->categoria_id
            && ! array_key_exists('subcategoria_id', $data)) {
            $data['subcategoria_id'] = null;
        }

        $registro->update($data);
        $registro->load($this->relacionesVisiblesPara($request->user()->getAuthIdentifier()));

        return EgresoResource::make($registro);
    }

    public function destroy(Request $request, int $egreso): JsonResponse
    {
        $registro = $request->user()->egresos()->findOrFail($egreso);
        $registro->delete();

        return response()->json(null, 204);
    }

    /**
     * Limita las relaciones serializadas a categorías del sistema o del usuario autenticado.
     *
     * @return array<string, \Closure(Builder): void>
     */
    private function relacionesVisiblesPara(int|string $userId): array
    {
        $categoriaPermitida = fn (Builder $query) => $query
            ->where('tipo', 'egreso')
            ->where(fn (Builder $query) => $query->whereNull('user_id')
                ->orWhere('user_id', $userId));

        return [
            'categoria' => $categoriaPermitida,
            'subcategoria' => fn (Builder $query) => $query->whereHas('categoria', $categoriaPermitida),
        ];
    }
}
