<?php

namespace Database\Factories;

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
            'user_id' => random_int(1, 3),
            'full_name' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'street_name' => $this->faker->streetName(),
            'suburb' => $this->faker->citySuffix(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),   
        ];
    }
}
