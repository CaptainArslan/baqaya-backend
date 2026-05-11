<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'business_type' => fake()->randomElement(['retail', 'wholesale', 'services']),
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
        ];
    }
}
