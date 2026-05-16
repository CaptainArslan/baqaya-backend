<?php

declare(strict_types=1);

namespace App\OpenApi;

/**
 * @OA\Parameter(
 *     parameter="UuidPath",
 *     name="uuid",
 *     in="path",
 *     required=true,
 *     description="Public UUID/ULID of the resource",
 *
 *     @OA\Schema(type="string", example="01JXYZABCDEFGHJKMNPQRSTVW")
 * )
 *
 * @OA\Tag(name="Auth", description="OTP login, token refresh, logout")
 * @OA\Tag(name="Profile", description="Authenticated user profile")
 * @OA\Tag(name="Shop", description="Shop tenancy and settings")
 * @OA\Tag(name="Customers", description="Customer management")
 * @OA\Tag(name="Transactions", description="Ledger credits, corrections, reversals, adjustments")
 * @OA\Tag(name="Payments", description="Payment records, corrections, reversals")
 * @OA\Tag(name="Sync", description="Offline sync push and pull")
 * @OA\Tag(name="Reminders", description="WhatsApp payment reminders")
 * @OA\Tag(name="Statements", description="Customer PDF statements")
 */
final class OpenApiSpec {}
