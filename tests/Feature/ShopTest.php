<?php

declare(strict_types=1);

it('creates and fetches shop without uuid header', function (): void {
    $user = createUser();
    $token = $user->createToken('test-device')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/shop', [
        'name' => 'Arslan Kiryana',
        'phone' => '+923001111111',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Arslan Kiryana');

    $this->withToken($token)
        ->getJson('/api/v1/shop')
        ->assertOk()
        ->assertJsonPath('data.name', 'Arslan Kiryana');
});

it('rejects creating a second shop for the same user', function (): void {
    ['token' => $token] = createUserWithShop();

    $this->withToken($token)->postJson('/api/v1/shop', [
        'name' => 'Another Shop',
    ])->assertUnprocessable()
        ->assertJsonPath('code', 'SHOP_ALREADY_EXISTS');
});
