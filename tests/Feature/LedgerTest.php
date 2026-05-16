<?php

declare(strict_types=1);

use App\Models\Customer;

it('adds credit transaction and updates balance', function (): void {
    ['shop' => $shop, 'token' => $token, 'user' => $user] = createUserWithShop();

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'current_balance' => 0,
        'opening_balance' => 0,
    ]);

    $response = $this->withToken($token)
        ->postJson('/api/v1/transactions', [
            'customer_uuid' => $customer->uuid,
            'amount' => 1000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Udhar',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.amount', '1000.00');

    expect($customer->fresh()->current_balance)->toBe('1000.00');
});

it('records payment and reduces balance', function (): void {
    ['shop' => $shop, 'token' => $token] = createUserWithShop();

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'current_balance' => 1000,
        'opening_balance' => 1000,
    ]);

    $this->withToken($token)
        ->postJson('/api/v1/payments', [
            'customer_uuid' => $customer->uuid,
            'amount' => 400,
            'payment_date' => now()->toDateString(),
        ])
        ->assertCreated();

    expect($customer->fresh()->current_balance)->toBe('600.00');
});

it('corrects transaction via reversal flow', function (): void {
    ['shop' => $shop, 'token' => $token] = createUserWithShop();

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'current_balance' => 0,
    ]);

    $create = $this->withToken($token)
        ->postJson('/api/v1/transactions', [
            'customer_uuid' => $customer->uuid,
            'amount' => 1000,
            'transaction_date' => now()->toDateString(),
        ]);

    $uuid = $create->json('data.uuid');

    $this->withToken($token)
        ->postJson("/api/v1/transactions/{$uuid}/correction", [
            'amount' => 800,
            'correction_reason' => 'Wrong amount',
            'transaction_date' => now()->toDateString(),
        ])
        ->assertOk();

    expect($customer->fresh()->current_balance)->toBe('800.00');
});

it('updates metadata without changing balance', function (): void {
    ['shop' => $shop, 'token' => $token] = createUserWithShop();

    $customer = Customer::factory()->create([
        'shop_id' => $shop->id,
        'current_balance' => 0,
        'opening_balance' => 0,
    ]);

    $create = $this->withToken($token)
        ->postJson('/api/v1/transactions', [
            'customer_uuid' => $customer->uuid,
            'amount' => 500,
            'transaction_date' => now()->toDateString(),
        ]);

    $uuid = $create->json('data.uuid');

    $this->withToken($token)
        ->patchJson("/api/v1/transactions/{$uuid}/metadata", [
            'notes' => 'Updated note only',
        ])
        ->assertOk();

    expect($customer->fresh()->current_balance)->toBe('500.00');
});
