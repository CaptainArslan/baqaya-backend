<?php

declare(strict_types=1);

namespace App\OpenApi\Paths;

/**
 * @OA\Post(
 *     path="/api/v1/reminders/send",
 *     operationId="remindersSend",
 *     tags={"Reminders"},
 *     summary="Queue WhatsApp reminder",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"customer_uuid","message"},
 *
 *             @OA\Property(property="customer_uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *             @OA\Property(property="message", type="string", example="Assalam o Alaikum, aap ka baqaya 1500 PKR hai.")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=202,
 *         description="Reminder queued",
 *
 *         @OA\JsonContent(
 *             allOf={
 *
 *                 @OA\Schema(ref="#/components/schemas/ApiSuccessResponse"),
 *                 @OA\Schema(@OA\Property(property="data", ref="#/components/schemas/Reminder"))
 *             }
 *         )
 *     ),
 *
 *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
 * )
 *
 * @OA\Get(
 *     path="/api/v1/reminders",
 *     operationId="remindersIndex",
 *     tags={"Reminders"},
 *     summary="Reminder history",
 *     security={{"bearerAuth":{}}},
 *     parameters={
 *
 *         @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=20))
 *     },
 *
 *     @OA\Response(response=200, description="Paginated reminders", @OA\JsonContent(ref="#/components/schemas/ApiSuccessResponse")),
 *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse"))
 * )
 */
final class ReminderPaths {}
