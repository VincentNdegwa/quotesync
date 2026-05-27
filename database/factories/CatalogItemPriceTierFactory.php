<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Models\CatalogItemPriceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogItemPriceTier>
 */
class CatalogItemPriceTierFactory extends Factory
{
    protected $model = CatalogItemPriceTier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'catalog_item_id' => CatalogItem::factory(),
            'min_quantity' => fake()->numberBetween(1, 10),
            'max_quantity' => fake()->optional()->numberBetween(11, 100),
            'pricing_type' => fake()->randomElement(['fixed_price', 'discount_percent']),
            'value' => fake()->randomFloat(2, 0, 1000),
        ];
    }
}
