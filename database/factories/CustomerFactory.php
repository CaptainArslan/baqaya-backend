<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => fake()->name(),
            'phone' => null,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'server_version' => 1,
        ];
    }

    public function withPhone(): static
    {
        return $this->state(fn () => [
            'phone' => '+9230'.fake()->unique()->numerify('########'),
        ]);
    }

    public function withBalance(float $balance): static
    {
        return $this->state(fn () => [
            'opening_balance' => $balance,
            'current_balance' => $balance,
        ]);
    }
}
