<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => random_int(1, 10),
            'full_name' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'area' => $this->faker->name(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
        ];
    }
}
