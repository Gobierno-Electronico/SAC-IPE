<?php

namespace Database\Factories;

use App\Enums\RolEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name,
            'usuario' => $this->faker->unique()->userName,
            'apellido_materno' => $this->faker->lastName,
            'apellido_paterno' => $this->faker->lastName,
            'rol' => RolEnum::ADMINISTRADOR,
            'numero_de_personal' => $this->faker->unique()->numberBetween(1000, 9999),
            'password' => bcrypt('123456789'),  // you can use Hash::make('password') as well
            'password_restaurada' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
