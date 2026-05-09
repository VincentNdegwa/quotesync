<?php

namespace Database\Factories;

use App\Models\CatalogItem;
use App\Models\CatalogItemVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogItemVariant>
 */
class CatalogItemVariantFactory extends Factory
{
    protected $model = CatalogItemVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'catalog_item_id' => CatalogItem::factory(),
            'name' => fake()->word(),
            'sku' => fake()->optional()->word(),
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'cost_price' => fake()->optional()->randomFloat(2, 5, 500),
            'is_default' => false,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
