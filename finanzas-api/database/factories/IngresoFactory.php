<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Ingreso;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ingreso>
 */
class IngresoFactory extends Factory
{
    protected $model = Ingreso::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'categoria_id' => Categoria::factory()->ingreso(),
            'fecha' => fake()->dateTimeBetween('2026-01-01', '2026-07-31')->format('Y-m-d'),
            'fuente' => fake()->randomElement(['Trabajo de medio tiempo', 'Proyecto freelance', 'Apoyo familiar']),
            'monto' => fake()->numberBetween(1800, 3500).'.00',
            'notas' => fake()->optional()->sentence(),
        ];
    }
}
