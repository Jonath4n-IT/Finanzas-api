<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\Ingreso;
use App\Models\Subcategoria;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            'ana' => User::updateOrCreate(
                ['email' => 'ana.lopez@example.com'],
                [
                    'name' => 'Ana López',
                    'password' => Hash::make('Finanzas2026!'),
                ],
            ),
            'carlos' => User::updateOrCreate(
                ['email' => 'carlos.mendez@example.com'],
                [
                    'name' => 'Carlos Méndez',
                    'password' => Hash::make('Finanzas2026!'),
                ],
            ),
        ];

        $categorias = Categoria::query()
            ->whereNull('user_id')
            ->get()
            ->keyBy('nombre');

        $subcategorias = Subcategoria::query()
            ->get()
            ->keyBy(fn (Subcategoria $subcategoria): string => $subcategoria->categoria_id.'|'.$subcategoria->nombre);

        $meses = [1, 2, 3, 4, 5, 7];
        $ingresos = [
            1 => ['día' => 5, 'fuente' => 'Trabajo de medio tiempo', 'categoria' => 'Empleo', 'ana' => '2600.00', 'carlos' => '2400.00'],
            2 => ['día' => 5, 'fuente' => 'Trabajo de medio tiempo', 'categoria' => 'Empleo', 'ana' => '2600.00', 'carlos' => '2400.00'],
            3 => ['día' => 8, 'fuente' => 'Proyecto de diseño freelance', 'categoria' => 'Freelance / Proyecto', 'ana' => '3000.00', 'carlos' => '2800.00'],
            4 => ['día' => 5, 'fuente' => 'Trabajo de medio tiempo', 'categoria' => 'Empleo', 'ana' => '2700.00', 'carlos' => '2500.00'],
            5 => ['día' => 10, 'fuente' => 'Proyecto de programación freelance', 'categoria' => 'Freelance / Proyecto', 'ana' => '3200.00', 'carlos' => '3000.00'],
            7 => ['día' => 5, 'fuente' => 'Trabajo de medio tiempo', 'categoria' => 'Empleo', 'ana' => '2700.00', 'carlos' => '2500.00'],
        ];

        $plantillasEgresos = [
            ['día' => 2, 'categoria' => 'Vivienda', 'subcategoria' => 'Alquiler', 'descripcion' => 'Pago de habitación', 'monto' => 850],
            ['día' => 4, 'categoria' => 'Educación', 'subcategoria' => 'Universidad', 'descripcion' => 'Cuota universitaria', 'monto' => 450],
            ['día' => 7, 'categoria' => 'Alimentación', 'subcategoria' => 'Supermercado', 'descripcion' => 'Compra de supermercado', 'monto' => 280],
            ['día' => 10, 'categoria' => 'Transporte', 'subcategoria' => 'Bus', 'descripcion' => 'Recargas de transporte', 'monto' => 160],
            ['día' => 13, 'categoria' => 'Salud', 'subcategoria' => 'Medicamentos', 'descripcion' => 'Medicamentos', 'monto' => 90],
            ['día' => 16, 'categoria' => 'Ocio / Entretenimiento', 'subcategoria' => 'Cine', 'descripcion' => 'Salida al cine', 'monto' => 75],
            ['día' => 20, 'categoria' => 'Deporte', 'subcategoria' => 'Gimnasio', 'descripcion' => 'Mensualidad de gimnasio', 'monto' => 150],
            ['día' => 24, 'categoria' => 'Imprevistos', 'subcategoria' => 'Reparaciones', 'descripcion' => 'Reparación menor', 'monto' => 110],
            ['día' => 27, 'categoria' => 'Otro Egreso', 'subcategoria' => null, 'descripcion' => 'Gasto personal varios', 'monto' => 120],
        ];

        foreach ($usuarios as $claveUsuario => $usuario) {
            foreach ($meses as $indiceMes => $mes) {
                $ingreso = $ingresos[$mes];
                $fechaIngreso = sprintf('2026-%02d-%02d', $mes, $ingreso['día']);

                Ingreso::updateOrCreate(
                    [
                        'user_id' => $usuario->id,
                        'fecha' => $fechaIngreso,
                        'fuente' => $ingreso['fuente'],
                    ],
                    [
                        'categoria_id' => $categorias->get($ingreso['categoria'])->id,
                        'monto' => $ingreso[$claveUsuario],
                        'notas' => 'Ingreso de prueba para el dashboard.',
                    ],
                );

                foreach ($plantillasEgresos as $indiceEgreso => $egreso) {
                    $fechaEgreso = sprintf('2026-%02d-%02d', $mes, $egreso['día']);
                    $ajuste = ($indiceMes * 10) + ($claveUsuario === 'ana' ? 0 : 20);
                    $subcategoriaId = $egreso['subcategoria'] === null
                        ? null
                        : $subcategorias->get($categorias->get($egreso['categoria'])->id.'|'.$egreso['subcategoria'])->id;

                    Egreso::updateOrCreate(
                        [
                            'user_id' => $usuario->id,
                            'fecha' => $fechaEgreso,
                            'descripcion' => $egreso['descripcion'],
                        ],
                        [
                            'categoria_id' => $categorias->get($egreso['categoria'])->id,
                            'subcategoria_id' => $subcategoriaId,
                            'monto' => ($egreso['monto'] + $ajuste).'.00',
                            'notas' => 'Egreso de prueba para el dashboard.',
                        ],
                    );
                }
            }
        }
    }
}
