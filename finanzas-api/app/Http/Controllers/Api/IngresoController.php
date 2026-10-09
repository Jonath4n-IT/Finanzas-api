<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IngresoIndexRequest;
use App\Http\Requests\IngresoRequest;
use App\Http\Resources\Ingresos\IngresoCollection;
use App\Http\Resources\Ingresos\IngresoResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngresoController extends Controller
{
    public function index(IngresoIndexRequest $request): IngresoCollection
    {
        $filters = $request->validated();
        $user = $request->user();
        $query = $user
            ->ingresos()
            ->with(['categoria' => $this->categoriaPermitida($user->getAuthIdentifier())])
            ->when(isset($filters['anio']), fn ($query) => $query->whereYear('fecha', $filters['anio']))
            ->when(isset($filters['mes']), fn ($query) => $query->whereMonth('fecha', $filters['mes']))
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        return IngresoCollection::make($query->paginate(15));
    }

    public function store(IngresoRequest $request): JsonResponse
    {
        $ingreso = $request->user()->ingresos()->create($request->validated());
        $ingreso->load(['categoria' => $this->categoriaPermitida($request->user()->getAuthIdentifier())]);

        return IngresoResource::make($ingreso)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $ingreso): IngresoResource
    {
        $registro = $request->user()
            ->ingresos()
            ->with(['categoria' => $this->categoriaPermitida($request->user()->getAuthIdentifier())])
            ->findOrFail($ingreso);

        return IngresoResource::make($registro);
    }

    public function update(IngresoRequest $request, int $ingreso): IngresoResource
    {
        $registro = $request->user()->ingresos()->findOrFail($ingreso);
        $registro->update($request->validated());
        $registro->load(['categoria' => $this->categoriaPermitida($request->user()->getAuthIdentifier())]);

        return IngresoResource::make($registro);
    }

    public function destroy(Request $request, int $ingreso): JsonResponse
    {
        $registro = $request->user()->ingresos()->findOrFail($ingreso);
        $registro->delete();

        return response()->json(null, 204);
    }

    /**
     * Restringe la categoría serializada a categorías de ingreso del sistema o del usuario.
     */
    private function categoriaPermitida(int|string $userId): \Closure
    {
        return fn (Builder $query) => $query
            ->where('tipo', 'ingreso')
            ->where(fn (Builder $query) => $query->whereNull('user_id')
                ->orWhere('user_id', $userId));
    }
}
