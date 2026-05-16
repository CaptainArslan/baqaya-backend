<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

/**
 * @OA\Post(
 *     path="/api/v1/shop",
 *     operationId="shopStore",
 *     tags={"Shop"},
 *     summary="Create shop",
 *     description="Creates the authenticated user's shop. Each user may own exactly one shop.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"name"},
 *
 *             @OA\Property(property="name", type="string", example="Arslan Kiryana"),
 *             @OA\Property(property="business_type", type="string", example="grocery"),
 *             @OA\Property(property="phone", type="string", example="+923001111111"),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="currency", type="string", example="PKR"),
 *             @OA\Property(property="timezone", type="string", example="Asia/Karachi")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Shop created",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Shop"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=422, description="Validation error or shop already exists", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/api/v1/shop",
 *     operationId="shopShow",
 *     tags={"Shop"},
 *     summary="Get authenticated user's shop",
 *     description="Returns the shop owned by the authenticated user. No shop UUID header is required.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(
 *         response=200,
 *         description="The user's shop",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Shop"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=404, description="Shop not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/api/v1/shop",
 *     operationId="shopUpdate",
 *     tags={"Shop"},
 *     summary="Update authenticated user's shop",
 *     description="Updates the shop owned by the authenticated user.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="phone", type="string"),
 *             @OA\Property(property="address", type="string")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Shop updated",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Shop"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/api/v1/shop",
 *     operationId="shopDestroy",
 *     tags={"Shop"},
 *     summary="Soft-delete authenticated user's shop",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Response(response=200, description="Shop deleted", @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 */
final class ShopPaths {}
