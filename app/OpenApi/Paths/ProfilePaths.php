<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

/**
 * @OA\Get(
 *     path="/api/v1/profile",
 *     operationId="profileShow",
 *     tags={"Profile"},
 *     summary="Get current user profile",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="Profile",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/User"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/api/v1/profile",
 *     operationId="profileUpdate",
 *     tags={"Profile"},
 *     summary="Update profile",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="name", type="string", example="Arslan Khan"),
 *             @OA\Property(property="email", type="string", nullable=true, example="owner@baqaya.pk"),
 *             @OA\Property(property="language", type="string", example="ur")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Updated profile",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/User"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/api/v1/profile",
 *     operationId="profileDestroy",
 *     tags={"Profile"},
 *     summary="Soft-delete account",
 *     description="Soft-deletes the authenticated user account.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(response=200, description="Account deleted", @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"))
 * )
 */
final class ProfilePaths {}
