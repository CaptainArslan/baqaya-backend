<?php

declare(strict_types=1);

it('manages customers with search', function (): void {
    ['shop' => $shop, 'token' => $token] = createUserWithShop();

    $create = $this->withToken($token)
        ->postJson('/api/v1/customers', [
            'name' => 'Ali Ahmed',
            'phone' => '+923001112233',
            'opening_balance' => 500,
        ]);

    $create->assertCreated()
        ->assertJsonPath('data.name', 'Ali Ahmed')
        ->assertJsonPath('data.current_balance', '500.00');

    $uuid = $create->json('data.uuid');

    $this->withToken($token)
        ->getJson('/api/v1/customers?search=Ali')
        ->assertOk()
        ->assertJsonPath('data.0.uuid', $uuid);
});

it('rejects duplicate customer name within the same shop', function (): void {
    ['shop' => $shop, 'token' => $token] = createUserWithShop();

    $this->withToken($token)
        ->postJson('/api/v1/customers', ['name' => 'Unique Name'])
        ->assertCreated();

    $this->withToken($token)
        ->postJson('/api/v1/customers', ['name' => 'Unique Name'])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'DUPLICATE_CUSTOMER_NAME');
});
