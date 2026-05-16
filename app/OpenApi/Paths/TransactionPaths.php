<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

/**
 * @OA\Get(
 *     path="/api/v1/transactions",
 *     operationId="transactionsIndex",
 *     tags={"Transactions"},
 *     summary="List ledger transactions",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(name="customer_uuid", in="query", @OA\Schema(type="string")),
 *         @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20))
 *     },
 *
 *     @OA\Response(
 *         response=200,
 *         description="Paginated transactions",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction")))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/transactions",
 *     operationId="transactionsStore",
 *     tags={"Transactions"},
 *     summary="Add credit (ledger entry)",
 *     description="Creates an immutable credit transaction. Amount cannot be changed later — use correction endpoint.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/CreateTransactionBody")),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Transaction created",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Transaction"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")),
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")),
 *     @OA\Response(response=429, description="Rate limited", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/api/v1/transactions/{uuid}",
 *     operationId="transactionsShow",
 *     tags={"Transactions"},
 *     summary="Get transaction",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\Response(
 *         response=200,
 *         description="Transaction",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Transaction"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/transactions/{uuid}/correction",
 *     operationId="transactionsCorrect",
 *     tags={"Transactions"},
 *     summary="Correct transaction (financial edit)",
 *     description="Financially safe edit: keeps original record, creates reversal + corrected entry. Required for edit-transaction screens.",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TransactionCorrectionBody")),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Corrected transaction returned",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Transaction"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")),
 *     @OA\Response(response=422, description="Invalid status / validation", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/transactions/{uuid}/reverse",
 *     operationId="transactionsReverse",
 *     tags={"Transactions"},
 *     summary="Reverse transaction",
 *     description="Creates a reversal entry and marks the original as reversed. Does not delete history.",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TransactionReverseBody")),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Reversal transaction",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Transaction"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 *
 * @OA\Post(
 *     path="/api/v1/transactions/adjustment",
 *     operationId="transactionsAdjustment",
 *     tags={"Transactions"},
 *     summary="Create balance adjustment",
 *     description="Manual adjustment entry (in/out) without correcting an existing transaction.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/TransactionAdjustmentBody")),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Adjustment created",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Transaction"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Patch(
 *     path="/api/v1/transactions/{uuid}/metadata",
 *     operationId="transactionsMetadata",
 *     tags={"Transactions"},
 *     summary="Update non-financial metadata only",
 *     description="Updates notes, reference, description, category, attachment only. Does NOT change amount, customer, or balance.",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\RequestBody(@OA\JsonContent(ref="#/components/schemas/TransactionMetadataBody")),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Metadata updated",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Transaction"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 */
final class TransactionPaths {}
