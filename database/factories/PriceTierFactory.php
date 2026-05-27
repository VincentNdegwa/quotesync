<?php

namespace Database\Factories;

use App\Models\PriceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceTier>
 */
class PriceTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'priceable_id' => 1,
            'priceable_type' => 'quote_line_item',
            'variant_id' => null,
            'min_quantity' => fake()->numberBetween(1, 10),
            'max_quantity' => fake()->randomElement([-1, fake()->numberBetween(10, 100)]),
            'pricing_type' => fake()->randomElement(['fixed_price', 'discount_percent']),
            'unit_price' => fake()->randomFloat(2, 100, 10000),
            'discount_percent' => fake()->randomFloat(2, 1, 50),
        ];
    }
}
