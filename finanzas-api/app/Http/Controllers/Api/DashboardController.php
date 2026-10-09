<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest;
use App\Http\Resources\Dashboard\DashboardResource;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(DashboardRequest $request): DashboardResource
    {
        $user = $request->user();
        $filters = $request->validated();
        $anio = (int) ($filters['anio'] ?? now()->year);
        $mes = (int) ($filters['mes'] ?? now()->month);
        $inicioAnual = sprintf('%04d-01-01', $anio);
        $finAnual = sprintf('%04d-12-31', $anio);
        $inicioPeriodo = Carbon::create($anio, $mes, 1)->startOfMonth()->toDateString();
        $finPeriodo = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $resumen = $this->resumen($user, $inicioPeriodo, $finPeriodo);
        $resumenAnual = $this->resumen($user, $inicioAnual, $finAnual);

        return DashboardResource::make([
            'periodo' => [
                'anio' => $anio,
                'mes' => $mes,
            ],
            'resumen' => $resumen,
            'resumen_anual' => $resumenAnual,
            'movimientos' => [
                'ingresos' => $user->ingresos()->whereBetween('fecha', [$inicioPeriodo, $finPeriodo])->count(),
                'egresos' => $user->egresos()->whereBetween('fecha', [$inicioPeriodo, $finPeriodo])->count(),
            ],
            'tendencia_mensual' => $this->tendenciaMensual($user, $inicioAnual, $finAnual),
            'ingresos_por_categoria' => $this->ingresosPorCategoria($user, $inicioPeriodo, $finPeriodo),
            'egresos_por_categoria' => $this->egresosPorCategoria($user, $inicioPeriodo, $finPeriodo),
        ]);
    }

    /** @return array{ingresos: string, egresos: string, balance: string} */
    private function resumen(User $user, string $inicio, string $fin): array
    {
        $ingresos = $this->decimal($user->ingresos()->whereBetween('fecha', [$inicio, $fin])->sum('monto'));
        $egresos = $this->decimal($user->egresos()->whereBetween('fecha', [$inicio, $fin])->sum('monto'));

        return [
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'balance' => $this->decimalDesdeCentavos($this->centavos($ingresos) - $this->centavos($egresos)),
        ];
    }

    /** @return list<array{mes: int, ingresos: string, egresos: string}> */
    private function tendenciaMensual(User $user, string $inicio, string $fin): array
    {
        $expresionMes = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', fecha) AS INTEGER)"
            : 'MONTH(fecha)';

        $ingresos = $user->ingresos()
            ->whereBetween('fecha', [$inicio, $fin])
            ->selectRaw("{$expresionMes} as mes, SUM(monto) as total")
            ->groupByRaw($expresionMes)
            ->get()
            ->keyBy('mes');

        $egresos = $user->egresos()
            ->whereBetween('fecha', [$inicio, $fin])
            ->selectRaw("{$expresionMes} as mes, SUM(monto) as total")
            ->groupByRaw($expresionMes)
            ->get()
            ->keyBy('mes');

        $tendencia = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $tendencia[] = [
                'mes' => $mes,
                'ingresos' => $this->decimal($ingresos->get($mes)?->total),
                'egresos' => $this->decimal($egresos->get($mes)?->total),
            ];
        }

        return $tendencia;
    }

    /** @return list<array{categoria_id: int, categoria: ?string, monto: string}> */
    private function ingresosPorCategoria(User $user, string $inicio, string $fin): array
    {
        return $user->ingresos()
            ->whereBetween('fecha', [$inicio, $fin])
            ->select('categoria_id')
            ->selectRaw('SUM(monto) as total')
            ->with(['categoria' => $this->categoriaPermitida($user->getAuthIdentifier(), 'ingreso')])
            ->groupBy('categoria_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn (Ingreso $ingreso): array => [
                'categoria_id' => (int) $ingreso->categoria_id,
                'categoria' => $ingreso->categoria?->nombre,
                'monto' => $this->decimal($ingreso->total),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{categoria_id: int, categoria: ?string, monto: string}> */
    private function egresosPorCategoria(User $user, string $inicio, string $fin): array
    {
        return $user->egresos()
            ->whereBetween('fecha', [$inicio, $fin])
            ->select('categoria_id')
            ->selectRaw('SUM(monto) as total')
            ->with(['categoria' => $this->categoriaPermitida($user->getAuthIdentifier(), 'egreso')])
            ->groupBy('categoria_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($egreso): array => [
                'categoria_id' => (int) $egreso->categoria_id,
                'categoria' => $egreso->categoria?->nombre,
                'monto' => $this->decimal($egreso->total),
            ])
            ->values()
            ->all();
    }

    private function categoriaPermitida(int|string $userId, string $tipo): \Closure
    {
        return fn (Builder $query) => $query
            ->where('tipo', $tipo)
            ->where(fn (Builder $query) => $query->whereNull('user_id')
                ->orWhere('user_id', $userId));
    }

    private function decimal(mixed $amount): string
    {
        $amount = trim((string) ($amount ?? '0'));
        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '+-');
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');

        return ($negative && ($whole !== '0' || $fraction !== '00') ? '-' : '').$whole.'.'.$fraction;
    }

    private function centavos(string $amount): int
    {
        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '+-');
        [$whole, $fraction] = explode('.', $amount, 2);
        $centavos = ((int) $whole * 100) + (int) $fraction;

        return $negative ? -$centavos : $centavos;
    }

    private function decimalDesdeCentavos(int $amount): string
    {
        $negative = $amount < 0;
        $amount = abs($amount);
        $decimal = intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);

        return $negative ? '-'.$decimal : $decimal;
    }
}
