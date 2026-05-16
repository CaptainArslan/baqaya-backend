<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

/**
 * @OA\Post(
 *     path="/api/v1/sync/push",
 *     operationId="syncPush",
 *     tags={"Sync"},
 *     summary="Push offline operations",
 *     description="Processes a batch of offline operations with `request_id` and per-operation `operation_id` idempotency. Duplicate request_id returns cached response; duplicate operation_id is skipped.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SyncPushRequest")),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Batch processed",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/SyncPushResponse"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=409, description="Sync conflict", @OA\JsonContent(ref="#/components/schemas/SyncConflictResponse")),
 *     @OA\Response(response=429, description="Rate limited", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/api/v1/sync/pull",
 *     operationId="syncPull",
 *     tags={"Sync"},
 *     summary="Pull server changes",
 *     description="Returns entities with server_version greater than `since_version` and updates device sync cursor.",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(name="device_id", in="query", required=true, @OA\Schema(type="string")),
 *         @OA\Parameter(name="since_version", in="query", @OA\Schema(type="integer", default=0))
 *     },
 *
 *     @OA\Response(
 *         response=200,
 *         description="Changes since cursor",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/SyncPullResponse"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 */
final class SyncPaths {}
