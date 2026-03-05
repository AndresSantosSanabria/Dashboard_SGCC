<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'primer_nombre' => $this->faker->firstName,
            'primer_apellido' => $this->faker->lastName,
            'user' => $this->faker->unique()->userName,
            'password' => bcrypt('password'),
            'rol_id' => 1,
            'es_activo' => true,
        ];
    }
}
