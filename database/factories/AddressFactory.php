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
            'user_id' => User::factory(),
            'full_name' => $this->faker->name(),
            'postal_code' => $this->faker->postcode(),
            'street_name' => $this->faker->streetAddress(),
            'suburb' => $this->faker->citySuffix(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
        ];
    }
}
