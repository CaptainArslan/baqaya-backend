<?php

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

function createUser(array $attrs = []): User
{
    return User::factory()->create($attrs);
}

function createUserWithShop(array $userAttrs = [], array $shopAttrs = []): array
{
    $user = User::factory()->create($userAttrs);
    $shop = Shop::factory()->create(array_merge(['user_id' => $user->id], $shopAttrs));
    $user->devices()->create([
        'device_id' => 'test-device',
        'device_uuid' => (string) Str::ulid(),
        'platform' => 'testing',
    ]);
    $token = $user->createToken('test-device')->plainTextToken;

    return compact('user', 'shop', 'token');
}
