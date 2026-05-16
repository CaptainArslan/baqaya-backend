<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FinancialRecordStatus;
use App\Enums\TransactionType;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'customer_id' => Customer::factory(),
            'type' => TransactionType::Credit,
            'status' => FinancialRecordStatus::Active,
            'amount' => fake()->randomFloat(2, 100, 10000),
            'transaction_date' => now()->toDateString(),
            'balance_after' => 0,
            'created_by' => User::factory(),
            'server_version' => 1,
        ];
    }
}
