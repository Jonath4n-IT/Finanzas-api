<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subcategoria>
 */
class SubcategoriaFactory extends Factory
{
    protected $model = Subcategoria::class;

    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory()->egreso(),
            'nombre' => fake()->words(2, true),
        ];
    }
}
