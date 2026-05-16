<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\SyncRequest;
use Illuminate\Support\Str;

it('pushes sync operations idempotently by request_id', function (): void {
    ['shop' => $shop, 'token' => $token, 'user' => $user] = createUserWithShop();

    $user->devices()->create([
        'device_id' => 'sync-device-1',
        'device_uuid' => (string) Str::ulid(),
        'platform' => 'test',
    ]);

    $requestId = (string) Str::ulid();

    $payload = [
        'request_id' => $requestId,
        'device_id' => 'sync-device-1',
        'operations' => [
            [
                'operation_id' => (string) Str::ulid(),
                'type' => 'customer.create',
                'payload' => [
                    'name' => 'Sync Customer',
                    'phone' => '+923005551111',
                ],
            ],
        ],
    ];

    $first = $this->withToken($token)
        ->postJson('/api/v1/sync/push', $payload);

    $first->assertOk();

    $second = $this->withToken($token)
        ->postJson('/api/v1/sync/push', $payload);

    $second->assertOk()
        ->assertJsonPath('data.request_id', $requestId);

    expect(SyncRequest::query()->where('request_id', $requestId)->count())->toBe(1);
    expect(Customer::query()->where('shop_id', $shop->id)->where('name', 'Sync Customer')->count())->toBe(1);
});

it('pulls changes since cursor', function (): void {
    ['shop' => $shop, 'token' => $token, 'user' => $user] = createUserWithShop();

    $user->devices()->create([
        'device_id' => 'sync-device-2',
        'device_uuid' => (string) Str::ulid(),
    ]);

    Customer::factory()->create(['shop_id' => $shop->id, 'server_version' => 5]);

    $response = $this->withToken($token)
        ->getJson('/api/v1/sync/pull?device_id=sync-device-2&since_version=0');

    $response->assertOk()
        ->assertJsonStructure(['data' => ['cursor', 'customers', 'transactions', 'payments']]);
});

it('ignores duplicate operation_id', function (): void {
    ['shop' => $shop, 'token' => $token, 'user' => $user] = createUserWithShop();

    $user->devices()->create([
        'device_id' => 'sync-device-3',
        'device_uuid' => (string) Str::ulid(),
    ]);

    $operationId = (string) Str::ulid();

    $operation = [
        'operation_id' => $operationId,
        'type' => 'customer.create',
        'payload' => ['name' => 'Dup Op Customer'],
    ];

    $push = fn (string $requestId) => $this->withToken($token)
        ->postJson('/api/v1/sync/push', [
            'request_id' => $requestId,
            'device_id' => 'sync-device-3',
            'operations' => [$operation],
        ]);

    $push((string) Str::ulid())->assertOk();
    $push((string) Str::ulid())->assertOk();

    expect(Customer::query()->where('name', 'Dup Op Customer')->count())->toBe(1);
});
