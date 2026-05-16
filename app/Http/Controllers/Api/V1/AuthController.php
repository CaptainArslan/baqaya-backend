<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $key = 'otp:'.$validated['phone'];

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return ApiResponse::error('Too many OTP requests.', 'RATE_LIMITED', 429);
        }

        RateLimiter::hit($key, 300);

        $this->authService->requestOtp($validated['phone']);

        return ApiResponse::success(message: 'OTP sent.');
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'digits:'.$this->authService->otpLength()],
            'device_id' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
            'fcm_token' => ['nullable', 'string'],
        ]);

        $session = $this->authService->verifyOtp(
            $validated['phone'],
            $validated['code'],
            $validated['device_id'],
            $validated['device_name'] ?? null,
            $validated['platform'] ?? null,
            $validated['fcm_token'] ?? null,
        );

        return ApiResponse::success([
            'user' => new UserResource($session['user']),
            'device_uuid' => $session['device']->device_uuid,
            'access_token' => $session['access_token'],
            'refresh_token' => $session['refresh_token'],
            'token_type' => 'Bearer',
        ], 'Authenticated.');
    }

    public function refresh(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'refresh_token' => ['required', 'string'],
            'device_id' => ['required', 'string', 'max:255'],
        ]);

        $session = $this->authService->refreshToken(
            $validated['refresh_token'],
            $validated['device_id'],
        );

        return ApiResponse::success([
            'user' => new UserResource($session['user']),
            'access_token' => $session['access_token'],
            'refresh_token' => $session['refresh_token'],
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
        ]);

        $this->authService->logout($request->user(), $validated['device_id']);

        return ApiResponse::success(message: 'Logged out.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return ApiResponse::success(message: 'Logged out from all devices.');
    }
}
