<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditNote>
 */
class CreditNoteFactory extends Factory
{
    protected $model = CreditNote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'client_id' => Client::factory(),
            'number' => fake()->unique()->numerify('CN-####'),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'reason' => fake()->sentence(),
            'status' => fake()->randomElement(['draft', 'issued', 'applied']),
            'issued_at' => fake()->dateTimeThisYear(),
            'created_by' => 1, // Will be overridden in tests
        ];
    }
}
