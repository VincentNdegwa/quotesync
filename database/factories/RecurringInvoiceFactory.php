<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\RecurringInvoice;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringInvoice>
 */
class RecurringInvoiceFactory extends Factory
{
    protected $model = RecurringInvoice::class;

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
            'number' => fake()->unique()->numerify('RI-####'),
            'frequency' => fake()->randomElement(['monthly', 'quarterly', 'yearly']),
            'status' => fake()->randomElement(['active', 'paused', 'completed']),
            'start_date' => fake()->date(),
            'end_date' => fake()->optional()->date(),
            'next_invoice_date' => fake()->date(),
            'created_by' => 1, // Will be overridden in tests
        ];
    }
}
