<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderDetail>
 */
class OrderDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => random_int(1, 10),
            'user_id' => random_int(1, 3),
            'address_id' => random_int(1, 3),
            'status' => $this->faker->randomElement(['pending', 'paid', 'canceled']),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }
}
