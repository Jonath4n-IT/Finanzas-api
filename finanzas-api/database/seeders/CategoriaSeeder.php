<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'ingreso' => [
                'Empleo',
                'Freelance / Proyecto',
                'Negocio Propio',
                'Inversión / Dividendos',
                'Bono / Extra',
                'Otro Ingreso',
            ],
            'egreso' => [
                'Vivienda' => ['Alquiler', 'Agua', 'Luz', 'Internet'],
                'Educación' => ['Universidad', 'Cursos', 'Libros'],
                'Alimentación' => ['Supermercado', 'Restaurante', 'Almuerzo'],
                'Transporte' => ['Gasolina', 'Bus', 'Taxi / Uber', 'Parqueo'],
                'Salud' => ['Consulta', 'Medicamentos', 'Laboratorio'],
                'Ocio / Entretenimiento' => ['Suscripciones', 'Cine', 'Salidas'],
                'Deporte' => ['Gimnasio', 'Equipo deportivo'],
                'Imprevistos' => ['Emergencias', 'Reparaciones'],
                'Otro Egreso' => [],
            ],
        ];

        foreach ($categorias['ingreso'] as $nombre) {
            Categoria::firstOrCreate([
                'user_id' => null,
                'nombre' => $nombre,
                'tipo' => 'ingreso',
            ]);
        }

        foreach ($categorias['egreso'] as $nombre => $subcategorias) {
            $categoria = Categoria::firstOrCreate([
                'user_id' => null,
                'nombre' => $nombre,
                'tipo' => 'egreso',
            ]);

            foreach ($subcategorias as $nombreSubcategoria) {
                Subcategoria::firstOrCreate([
                    'categoria_id' => $categoria->id,
                    'nombre' => $nombreSubcategoria,
                ]);
            }
        }
    }
}
