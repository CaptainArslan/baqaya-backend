<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

/**
 * @OA\Post(
 *     path="/api/v1/auth/otp/request",
 *     operationId="authOtpRequest",
 *     tags={"Auth"},
 *     summary="Request OTP",
 *     description="Sends a one-time password to the given phone number. Rate limited to 5 requests per 5 minutes per phone.",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(ref="#/components/schemas/OtpRequestBody")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="OTP sent",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="message", example="OTP sent."))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
 *     @OA\Response(response=429, description="Rate limited", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/auth/otp/verify",
 *     operationId="authOtpVerify",
 *     tags={"Auth"},
 *     summary="Verify OTP and obtain tokens",
 *     description="Verifies OTP, registers/updates device, returns Sanctum `access_token` and rotating `refresh_token`. Copy `access_token` into Authorize.",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(ref="#/components/schemas/OtpVerifyBody")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Authenticated",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(
 *
 *                     @OA\Property(property="message", example="Authenticated."),
 *                     @OA\Property(property="data", ref="#/components/schemas/AuthTokenResponse")
 *                 )
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=422, description="Invalid OTP", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/auth/token/refresh",
 *     operationId="authTokenRefresh",
 *     tags={"Auth"},
 *     summary="Refresh access token",
 *     description="Rotates refresh token and returns a new access token pair.",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(ref="#/components/schemas/TokenRefreshBody")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Token refreshed",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/AuthTokenResponse"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Invalid refresh token", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/auth/logout",
 *     operationId="authLogout",
 *     tags={"Auth"},
 *     summary="Logout current device",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(ref="#/components/schemas/LogoutBody")
 *     ),
 *
 *     @OA\Response(response=200, description="Logged out", @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/auth/logout-all",
 *     operationId="authLogoutAll",
 *     tags={"Auth"},
 *     summary="Logout all devices",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(response=200, description="Logged out from all devices", @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"))
 * )
 */
final class AuthPaths {}
