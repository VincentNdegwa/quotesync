<?php

namespace Database\Factories;

use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
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
            'name' => fake()->randomElement(['VAT', 'GST', 'Withholding Tax', 'Service Charge']),
            'rate' => fake()->randomFloat(2, 0, 20),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
