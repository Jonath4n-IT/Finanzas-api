<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaIndexRequest;
use App\Http\Requests\CategoriaRequest;
use App\Http\Resources\Categorias\CategoriaCollection;
use App\Http\Resources\Categorias\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(CategoriaIndexRequest $request): CategoriaCollection
    {
        $user = $request->user();
        $filters = $request->validated();
        $query = $this->categoriasVisiblesPara($user->getAuthIdentifier())
            ->with(['subcategorias' => fn (Builder $query) => $query->orderBy('nombre')])
            ->when(isset($filters['tipo']), fn ($query) => $query->where('tipo', $filters['tipo']))
            ->orderBy('tipo')
            ->orderBy('nombre');

        return CategoriaCollection::make($query->paginate(50));
    }

    public function store(CategoriaRequest $request): JsonResponse
    {
        $categoria = $request->user()->categorias()->create($request->validated());
        $categoria->load('subcategorias');

        return CategoriaResource::make($categoria)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $categoria): CategoriaResource
    {
        $registro = $this->categoriasVisiblesPara($request->user()->getAuthIdentifier())
            ->with(['subcategorias' => fn (Builder $query) => $query->orderBy('nombre')])
            ->findOrFail($categoria);

        return CategoriaResource::make($registro);
    }

    public function update(CategoriaRequest $request, int $categoria): CategoriaResource|JsonResponse
    {
        $user = $request->user();
        $registro = $user->categorias()->findOrFail($categoria);
        $data = $request->validated();

        if (isset($data['tipo']) && $data['tipo'] !== $registro->tipo) {
            $tieneMovimientos = $user->ingresos()->where('categoria_id', $registro->id)->exists()
                || $user->egresos()->where('categoria_id', $registro->id)->exists()
                || $registro->subcategorias()->exists();

            if ($tieneMovimientos) {
                return response()->json([
                    'message' => 'No puedes cambiar el tipo de una categoría que ya tiene movimientos o subcategorías.',
                ], 409);
            }
        }

        $registro->update($data);
        $registro->load(['subcategorias' => fn (Builder $query) => $query->orderBy('nombre')]);

        return CategoriaResource::make($registro);
    }

    public function destroy(Request $request, int $categoria): JsonResponse
    {
        $user = $request->user();
        $registro = $user->categorias()->findOrFail($categoria);
        $tieneMovimientos = $user->ingresos()->where('categoria_id', $registro->id)->exists()
            || $user->egresos()->where('categoria_id', $registro->id)->exists();

        if ($tieneMovimientos) {
            return response()->json([
                'message' => 'No puedes eliminar una categoría que tiene movimientos registrados.',
            ], 409);
        }

        $registro->delete();

        return response()->json(null, 204);
    }

    private function categoriasVisiblesPara(int|string $userId): Builder
    {
        return Categoria::query()->where(fn (Builder $query) => $query
            ->whereNull('user_id')
            ->orWhere('user_id', $userId));
    }
}
