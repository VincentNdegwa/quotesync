<?php

namespace Database\Factories;

use App\Models\InvoiceReminder;
use App\Models\Invoice;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceReminder>
 */
class InvoiceReminderFactory extends Factory
{
    protected $model = InvoiceReminder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'workspace_id' => Workspace::factory(),
            'reminder_type' => fake()->randomElement(['first', 'second', 'third', 'final']),
            'scheduled_at' => fake()->dateTimeBetween('now', '+30 days'),
            'sent_at' => fake()->optional()->dateTime(),
            'status' => fake()->randomElement(['pending', 'sent', 'failed']),
            'error_message' => fake()->optional()->sentence(),
        ];
    }
}
