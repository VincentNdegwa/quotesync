<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'client_id' => null,
            'number' => fake()->unique()->numerify('QS-######'),
            'title' => fake()->sentence(),
            'status' => QuoteStatus::Draft->value,
            'currency' => 'USD',
            'base_currency' => 'USD',
            'fx_rate' => null,
            'subtotal' => fake()->randomFloat(2, 100, 10000),
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => fake()->randomFloat(2, 100, 10000),
            'base_total' => fake()->randomFloat(2, 100, 10000),
            'valid_until' => fake()->dateTimeBetween('+1 month', '+3 months')->format('Y-m-d'),
            'cover_message' => fake()->paragraph(),
            'terms' => fake()->paragraph(),
            'notes' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
            'assigned_to' => null,
            'approval_granted' => false,
            'approval_granted_at' => null,
            'requires_deposit' => false,
            'deposit_amount' => null,
            'sent_at' => null,
            'accepted_at' => null,
            'declined_at' => null,
            'decline_reason' => null,
            'signature_path' => null,
            'signer_name' => null,
            'signer_ip' => null,
            'win_probability' => null,
            'won_at' => null,
            'lost_at' => null,
        ];
    }
}
