<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FinancialRecordStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'customer_id' => Customer::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'payment_method' => PaymentMethod::Cash,
            'payment_date' => now()->toDateString(),
            'balance_after' => 0,
            'status' => FinancialRecordStatus::Active,
            'created_by' => User::factory(),
            'server_version' => 1,
        ];
    }
}
