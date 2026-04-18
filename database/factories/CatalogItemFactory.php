<?php

namespace Database\Factories;

use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogItem>
 */
class CatalogItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $creator = User::factory();

        return [
            'created_by' => $creator,
            'workspace_id' => fn (array $attributes): int => User::query()->findOrFail($attributes['created_by'])->current_workspace_id,
            'catalog_category_id' => fn (array $attributes): int => CatalogCategory::factory()->create([
                'created_by' => $attributes['created_by'],
                'workspace_id' => User::query()->findOrFail($attributes['created_by'])->current_workspace_id,
            ])->id,
            'tax_id' => fn (array $attributes): int => Tax::factory()->create([
                'created_by' => $attributes['created_by'],
                'workspace_id' => User::query()->findOrFail($attributes['created_by'])->current_workspace_id,
            ])->id,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'sku' => strtoupper(fake()->bothify('SKU-####')),
            'unit' => fake()->randomElement(['hr', 'day', 'unit', 'sqm', 'kg', 'm', 'lot', 'month']),
            'unit_price' => fake()->randomFloat(2, 10, 1500),
            'cost_price' => fake()->randomFloat(2, 5, 900),
            'tax_rate' => fake()->randomFloat(2, 0, 20),
            'image_path' => null,
            'is_active' => true,
            'usage_count' => fake()->numberBetween(0, 200),
        ];
    }
}
