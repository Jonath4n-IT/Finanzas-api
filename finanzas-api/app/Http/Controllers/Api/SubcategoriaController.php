<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubcategoriaIndexRequest;
use App\Http\Requests\SubcategoriaRequest;
use App\Http\Resources\Subcategorias\SubcategoriaCollection;
use App\Http\Resources\Subcategorias\SubcategoriaResource;
use App\Models\Subcategoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubcategoriaController extends Controller
{
    public function index(SubcategoriaIndexRequest $request): SubcategoriaCollection
    {
        $userId = $request->user()->getAuthIdentifier();
        $filters = $request->validated();
        $query = $this->subcategoriasVisiblesPara($userId)
            ->with(['categoria' => $this->categoriaVisiblePara($userId)])
            ->when(isset($filters['categoria_id']), fn ($query) => $query->where('categoria_id', $filters['categoria_id']))
            ->orderBy('categoria_id')
            ->orderBy('nombre');

        return SubcategoriaCollection::make($query->paginate(50));
    }

    public function store(SubcategoriaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $categoria = $request->user()->categorias()
            ->where('tipo', 'egreso')
            ->findOrFail($data['categoria_id']);
        $subcategoria = $categoria->subcategorias()->create(['nombre' => $data['nombre']]);
        $subcategoria->load(['categoria' => $this->categoriaVisiblePara($request->user()->getAuthIdentifier())]);

        return SubcategoriaResource::make($subcategoria)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $subcategoria): SubcategoriaResource
    {
        $userId = $request->user()->getAuthIdentifier();
        $registro = $this->subcategoriasVisiblesPara($userId)
            ->with(['categoria' => $this->categoriaVisiblePara($userId)])
            ->findOrFail($subcategoria);

        return SubcategoriaResource::make($registro);
    }

    public function update(SubcategoriaRequest $request, int $subcategoria): SubcategoriaResource|JsonResponse
    {
        $user = $request->user();
        $registro = $this->subcategoriasPropiasPara($user->getAuthIdentifier())->findOrFail($subcategoria);
        $data = $request->validated();

        if (isset($data['categoria_id']) && (int) $data['categoria_id'] !== (int) $registro->categoria_id
            && $user->egresos()->where('subcategoria_id', $registro->id)->exists()) {
            return response()->json([
                'message' => 'No puedes mover una subcategoría que ya está usada en egresos.',
            ], 409);
        }

        $registro->update($data);
        $registro->load(['categoria' => $this->categoriaVisiblePara($user->getAuthIdentifier())]);

        return SubcategoriaResource::make($registro);
    }

    public function destroy(Request $request, int $subcategoria): JsonResponse
    {
        $user = $request->user();
        $registro = $this->subcategoriasPropiasPara($user->getAuthIdentifier())->findOrFail($subcategoria);

        if ($user->egresos()->where('subcategoria_id', $registro->id)->exists()) {
            return response()->json([
                'message' => 'No puedes eliminar una subcategoría que está usada en egresos.',
            ], 409);
        }

        $registro->delete();

        return response()->json(null, 204);
    }

    private function subcategoriasVisiblesPara(int|string $userId): Builder
    {
        return Subcategoria::query()->whereHas('categoria', function (Builder $query) use ($userId): void {
            $query->where('tipo', 'egreso')
                ->where(fn (Builder $query) => $query->whereNull('user_id')
                    ->orWhere('user_id', $userId));
        });
    }

    private function subcategoriasPropiasPara(int|string $userId): Builder
    {
        return Subcategoria::query()->whereHas('categoria', fn (Builder $query) => $query
            ->where('tipo', 'egreso')
            ->where('user_id', $userId));
    }

    private function categoriaVisiblePara(int|string $userId): \Closure
    {
        return fn (Builder $query) => $query
            ->where('tipo', 'egreso')
            ->where(fn (Builder $query) => $query->whereNull('user_id')
                ->orWhere('user_id', $userId));
    }
}
