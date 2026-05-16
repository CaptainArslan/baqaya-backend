<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="ApiSuccessResponse",
 *     type="object",
 *     required={"success","message"},
 *
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", description="Response payload (shape varies by endpoint)"),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="last_page", type="integer", example=3),
 *         @OA\Property(property="per_page", type="integer", example=20),
 *         @OA\Property(property="total", type="integer", example=42)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ApiErrorResponse",
 *     type="object",
 *     required={"success","message","code"},
 *
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Resource not found."),
 *     @OA\Property(property="code", type="string", example="NOT_FOUND"),
 *     @OA\Property(property="errors", type="object", nullable=true, additionalProperties=true),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         nullable=true,
 *         description="Extra context (e.g. sync conflict)",
 *         @OA\Property(property="latest_server_version", type="integer", example=123)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/ApiErrorResponse"),
 *         @OA\Schema(
 *             type="object",
 *
 *             @OA\Property(
 *                 property="code",
 *                 type="string",
 *                 example="VALIDATION_ERROR"
 *             ),
 *             @OA\Property(
 *                 property="errors",
 *                 type="object",
 *                 example={"phone":{"The phone field is required."}}
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="UnauthorizedResponse",
 *     allOf={@OA\Schema(ref="#/components/schemas/ApiErrorResponse")},
 *     example={"success":false,"message":"Unauthenticated.","code":"UNAUTHENTICATED"}
 * )
 * @OA\Schema(
 *     schema="NotFoundResponse",
 *     allOf={@OA\Schema(ref="#/components/schemas/ApiErrorResponse")},
 *     example={"success":false,"message":"Resource not found.","code":"NOT_FOUND"}
 * )
 * @OA\Schema(
 *     schema="SyncConflictResponse",
 *     allOf={@OA\Schema(ref="#/components/schemas/ApiErrorResponse")},
 *     example={"success":false,"message":"Sync conflict.","code":"SYNC_CONFLICT","meta":{"latest_server_version":123}}
 * )
 */
final class Responses {}
