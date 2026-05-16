<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

/**
 * @OA\Get(
 *     path="/api/v1/payments",
 *     operationId="paymentsIndex",
 *     tags={"Payments"},
 *     summary="List payments",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(name="customer_uuid", in="query", @OA\Schema(type="string")),
 *         @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20))
 *     },
 *
 *     @OA\Response(
 *         response=200,
 *         description="Paginated payments",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Payment")))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/payments",
 *     operationId="paymentsStore",
 *     tags={"Payments"},
 *     summary="Record payment",
 *     description="Creates an immutable payment record. Use correction endpoint to change amount.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CreatePaymentBody")),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Payment recorded",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Payment"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/api/v1/payments/{uuid}",
 *     operationId="paymentsShow",
 *     tags={"Payments"},
 *     summary="Get payment",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\Response(
 *         response=200,
 *         description="Payment",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Payment"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/payments/{uuid}/correction",
 *     operationId="paymentsCorrect",
 *     tags={"Payments"},
 *     summary="Correct payment (financial edit)",
 *     description="Reversal + corrected payment. Required for edit-payment screens.",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/PaymentCorrectionBody")),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Corrected payment",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Payment"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/payments/{uuid}/reverse",
 *     operationId="paymentsReverse",
 *     tags={"Payments"},
 *     summary="Reverse payment",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"correction_reason"},
 *
 *             @OA\Property(property="correction_reason", type="string", example="Duplicate entry")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Reversal payment",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Payment"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/api/v1/payments/{uuid}/metadata",
 *     operationId="paymentsMetadata",
 *     tags={"Payments"},
 *     summary="Update payment metadata only",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/TransactionMetadataBody")),
 *
 *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 */
final class PaymentPaths {}
