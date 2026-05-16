<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

/**
 * @OA\Get(
 *     path="/api/v1/customers",
 *     operationId="customersIndex",
 *     tags={"Customers"},
 *     summary="List customers",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(name="search", in="query", @OA\Schema(type="string"), description="Search by name or phone"),
 *         @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20))
 *     },
 *
 *     @OA\Response(
 *         response=200,
 *         description="Paginated customers",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Customer")))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/customers",
 *     operationId="customersStore",
 *     tags={"Customers"},
 *     summary="Create customer",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"name"},
 *
 *             @OA\Property(property="name", type="string", example="Ali Ahmed"),
 *             @OA\Property(property="phone", type="string", example="+923001112233"),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="opening_balance", type="number", example=0),
 *             @OA\Property(property="notes", type="string")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Customer created",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Customer"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=422, description="Validation / duplicate phone", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/customers/bulk",
 *     operationId="customersBulkStore",
 *     tags={"Customers"},
 *     summary="Bulk create customers",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"customers"},
 *
 *             @OA\Property(
 *                 property="customers",
 *                 type="array",
 *
 *                 @OA\Items(
 *
 *                     @OA\Property(property="name", type="string", example="Hassan"),
 *                     @OA\Property(property="phone", type="string", example="+923002223344"),
 *                     @OA\Property(property="opening_balance", type="number", example=0)
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=201, description="Customers imported", @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/api/v1/customers/{uuid}",
 *     operationId="customersShow",
 *     tags={"Customers"},
 *     summary="Get customer",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\Response(
 *         response=200,
 *         description="Customer",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Customer"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/api/v1/customers/{uuid}",
 *     operationId="customersUpdate",
 *     tags={"Customers"},
 *     summary="Update customer",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\RequestBody(
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="phone", type="string"),
 *             @OA\Property(property="is_active", type="boolean")
 *         )
 *     ),
 *
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 *
 * @OA\Delete(
 *     path="/api/v1/customers/{uuid}",
 *     operationId="customersDestroy",
 *     tags={"Customers"},
 *     summary="Soft-delete customer",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 */
final class CustomerPaths {}
