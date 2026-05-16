<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *
 *     @OA\Property(property="uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="name", type="string", nullable=true, example="Arslan"),
 *     @OA\Property(property="phone", type="string", example="+923001234567"),
 *     @OA\Property(property="email", type="string", nullable=true, example="owner@shop.pk"),
 *     @OA\Property(property="language", type="string", example="en"),
 *     @OA\Property(property="last_login_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="AuthTokenResponse",
 *     type="object",
 *     description="Returned by OTP verify and token refresh. Copy `access_token` into Swagger Authorize.",
 *
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="device_uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="access_token", type="string", example="1|abcdefghijklmnopqrstuvwxyz"),
 *     @OA\Property(property="refresh_token", type="string", example="64charRefreshTokenPlainText"),
 *     @OA\Property(property="token_type", type="string", example="Bearer")
 * )
 *
 * @OA\Schema(
 *     schema="Shop",
 *     type="object",
 *
 *     @OA\Property(property="uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="name", type="string", example="Arslan Kiryana"),
 *     @OA\Property(property="business_type", type="string", nullable=true, example="grocery"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+923001111111"),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="currency", type="string", example="PKR"),
 *     @OA\Property(property="timezone", type="string", example="Asia/Karachi"),
 *     @OA\Property(property="server_version", type="integer", example=1)
 * )
 *
 * @OA\Schema(
 *     schema="Customer",
 *     type="object",
 *
 *     @OA\Property(property="uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="name", type="string", example="Ali Ahmed"),
 *     @OA\Property(property="phone", type="string", nullable=true, example="+923001112233"),
 *     @OA\Property(property="whatsapp_status", type="string", enum={"unknown","valid","invalid"}, example="unknown"),
 *     @OA\Property(property="address", type="string", nullable=true),
 *     @OA\Property(property="opening_balance", type="string", example="0.00"),
 *     @OA\Property(property="current_balance", type="string", example="1500.00"),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="server_version", type="integer", example=3),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *
 *     @OA\Property(property="uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="customer_uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="type", type="string", enum={"credit","payment","adjustment"}, example="credit"),
 *     @OA\Property(property="status", type="string", enum={"active","corrected","reversed","voided"}, example="active"),
 *     @OA\Property(property="amount", type="string", example="1000.00"),
 *     @OA\Property(property="direction", type="string", nullable=true, enum={"in","out"}),
 *     @OA\Property(property="reference_no", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="category", type="string", nullable=true),
 *     @OA\Property(property="transaction_date", type="string", format="date", example="2026-05-17"),
 *     @OA\Property(property="balance_after", type="string", example="2500.00"),
 *     @OA\Property(property="server_version", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *
 *     @OA\Property(property="uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="customer_uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="amount", type="string", example="500.00"),
 *     @OA\Property(property="payment_method", type="string", enum={"cash","bank","easypaisa","jazzcash","other"}, example="cash"),
 *     @OA\Property(property="status", type="string", enum={"active","corrected","reversed","voided"}, example="active"),
 *     @OA\Property(property="reference_no", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="payment_date", type="string", format="date", example="2026-05-17"),
 *     @OA\Property(property="balance_after", type="string", example="2000.00"),
 *     @OA\Property(property="server_version", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Reminder",
 *     type="object",
 *
 *     @OA\Property(property="uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="message", type="string", example="Please clear your baqaya."),
 *     @OA\Property(property="status", type="string", enum={"pending","queued","sent","failed"}, example="queued"),
 *     @OA\Property(property="scheduled_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="sent_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="server_version", type="integer", example=1)
 * )
 *
 * @OA\Schema(
 *     schema="StatementPdf",
 *     type="object",
 *
 *     @OA\Property(property="uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="status", type="string", enum={"pending","completed","failed"}, example="pending"),
 *     @OA\Property(property="from_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="to_date", type="string", format="date", nullable=true)
 * )
 */
final class Entities {}
