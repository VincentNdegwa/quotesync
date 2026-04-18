<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
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
            'company_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'whatsapp' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => fake()->countryCode(),
            'currency' => fake()->randomElement(['USD', 'NGN', 'KES', 'EUR']),
            'language' => fake()->randomElement(['en', 'fr', 'es', 'pt']),
            'tax_number' => strtoupper(fake()->bothify('TAX-####-??')),
        ];
    }
}
