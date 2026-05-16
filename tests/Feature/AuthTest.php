<?php

declare(strict_types=1);

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('requests otp for phone', function (): void {
    $response = $this->postJson('/api/v1/auth/otp/request', [
        'phone' => '+923001234567',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect(OtpCode::query()->where('phone', '+923001234567')->exists())->toBeTrue();
});

it('verifies otp and returns tokens', function (): void {
    $phone = '+923009999999';
    $code = '123456';

    OtpCode::query()->create([
        'phone' => $phone,
        'code_hash' => Hash::make($code),
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson('/api/v1/auth/otp/verify', [
        'phone' => $phone,
        'code' => $code,
        'device_id' => 'device-test-1',
        'device_name' => 'PHPUnit',
        'platform' => 'testing',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => ['access_token', 'refresh_token', 'user' => ['uuid', 'phone']],
        ]);

    expect(User::query()->where('phone', $phone)->exists())->toBeTrue();
});

it('logs out device', function (): void {
    ['user' => $user, 'token' => $token] = createUserWithShop();

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout', ['device_id' => 'test-device'])
        ->assertOk();
});
