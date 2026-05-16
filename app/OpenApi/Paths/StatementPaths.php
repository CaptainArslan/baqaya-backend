<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

/**
 * @OA\Post(
 *     path="/api/v1/customers/{uuid}/statement",
 *     operationId="statementsGenerate",
 *     tags={"Statements"},
 *     summary="Queue PDF statement generation",
 *     description="PDF is generated asynchronously via queue. Poll download endpoint when status is completed.",
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
 *             @OA\Property(property="from_date", type="string", format="date", example="2026-01-01"),
 *             @OA\Property(property="to_date", type="string", format="date", example="2026-05-17")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=202,
 *         description="Queued",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/StatementPdf"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=404, description="Customer not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse")),
 *     @OA\Response(response=429, description="Rate limited", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/api/v1/statements/{uuid}/download",
 *     operationId="statementsDownload",
 *     tags={"Statements"},
 *     summary="Download generated PDF",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(ref="#/components/parameters/UuidPath")
 *     },
 *
 *     @OA\Response(
 *         response=200,
 *         description="PDF file",
 *
 *         @OA\MediaType(
 *             mediaType="application/pdf",
 *
 *             @OA\Schema(type="string", format="binary")
 *         )
 *     ),
 *
 *     @OA\Response(response=409, description="Not ready", @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")),
 *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/NotFoundResponse"))
 * )
 */
final class StatementPaths {}
