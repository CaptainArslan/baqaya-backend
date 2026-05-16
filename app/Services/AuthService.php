<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\OtpCode;
use App\Models\RefreshToken;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthService
{
    public const OTP_LENGTH = 6;

    private const OTP_TTL_MINUTES = 10;

    private const REFRESH_TTL_DAYS = 30;

    public function requestOtp(string $phone): void
    {
        $code = $this->generateOtpCode();

        OtpCode::query()->where('phone', $phone)
            // ->whereNull('used_at')
            ->delete();

        Log::info('code: '.$code);

        OtpCode::query()->create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        if (app()->environment(['local', 'testing'])) {
            logger()->info('OTP for '.$phone.': '.$code);
        }
    }

    /**
     * @return array{user: User, device: UserDevice, access_token: string, refresh_token: string}
     */
    public function verifyOtp(
        string $phone,
        string $code,
        string $deviceId,
        ?string $deviceName = null,
        ?string $platform = null,
        ?string $fcmToken = null,
    ): array {
        $otp = OtpCode::query()
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($otp === null || ! Hash::check($code, $otp->code_hash)) {
            if ($otp !== null) {
                $otp->increment('attempts');
            }

            throw new ApiException('Invalid or expired OTP.', 'INVALID_OTP', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $otp->update(['used_at' => now()]);

        $user = User::query()->firstOrCreate(
            ['phone' => $phone],
            ['name' => null],
        );

        $user->update(['last_login_at' => now()]);

        $device = UserDevice::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $deviceId],
            [
                'device_uuid' => (string) Str::ulid(),
                'device_name' => $deviceName,
                'platform' => $platform,
                'fcm_token' => $fcmToken,
                'last_used_at' => now(),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ],
        );

        $accessToken = $user->createToken($device->device_id, ['*'])->plainTextToken;
        $refreshToken = $this->createRefreshToken($user, $device);

        return [
            'user' => $user,
            'device' => $device,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * @return array{user: User, device: UserDevice, access_token: string, refresh_token: string}
     */
    public function refreshToken(string $refreshTokenPlain, string $deviceId): array
    {
        $token = RefreshToken::query()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->get()
            ->first(fn (RefreshToken $t): bool => Hash::check($refreshTokenPlain, $t->token_hash));

        if ($token === null) {
            throw new ApiException('Invalid refresh token.', 'INVALID_REFRESH_TOKEN', Response::HTTP_UNAUTHORIZED);
        }

        $device = $token->device;

        if ($device->device_id !== $deviceId || $device->revoked_at !== null) {
            throw new ApiException('Device mismatch.', 'DEVICE_MISMATCH', Response::HTTP_UNAUTHORIZED);
        }

        $user = $token->user;
        $token->update(['revoked_at' => now()]);

        $accessToken = $user->createToken($device->device_id, ['*'])->plainTextToken;
        $newRefresh = $this->createRefreshToken($user, $device, $token);

        return [
            'user' => $user,
            'device' => $device,
            'access_token' => $accessToken,
            'refresh_token' => $newRefresh,
        ];
    }

    public function logout(User $user, string $deviceId): void
    {
        $user->tokens()->where('name', $deviceId)->delete();

        RefreshToken::query()
            ->where('user_id', $user->id)
            ->whereHas('device', fn ($q) => $q->where('device_id', $deviceId))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
        RefreshToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
        UserDevice::query()
            ->where('user_id', $user->id)
            ->update(['revoked_at' => now()]);
    }

    public function otpLength(): int
    {
        return self::OTP_LENGTH;
    }

    /**
     * Generate a numeric OTP code with the given number of digits.
     *
     * @param  int|null  $length  Defaults to {@see self::OTP_LENGTH}. Must be between 4 and 8.
     */
    public function generateOtpCode(?int $length = null): string
    {
        $length = $length ?? self::OTP_LENGTH;

        if ($length < 4 || $length > 8) {
            throw new \InvalidArgumentException('OTP length must be between 4 and 8 digits.');
        }

        $max = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function createRefreshToken(User $user, UserDevice $device, ?RefreshToken $replaced = null): string
    {
        $plain = Str::random(64);

        RefreshToken::query()
            ->where('user_device_id', $device->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        RefreshToken::create([
            'user_id' => $user->id,
            'user_device_id' => $device->id,
            'token_hash' => Hash::make($plain),
            'expires_at' => now()->addDays(self::REFRESH_TTL_DAYS),
            'replaced_by_token_id' => null,
        ]);

        return $plain;
    }
}
