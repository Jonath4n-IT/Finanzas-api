<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Egreso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Egreso>
 */
class EgresoFactory extends Factory
{
    protected $model = Egreso::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'categoria_id' => Categoria::factory()->egreso(),
            'subcategoria_id' => null,
            'fecha' => fake()->dateTimeBetween('2026-01-01', '2026-07-31')->format('Y-m-d'),
            'descripcion' => fake()->sentence(3),
            'monto' => fake()->numberBetween(30, 1500).'.00',
            'notas' => fake()->optional()->sentence(),
        ];
    }
}
