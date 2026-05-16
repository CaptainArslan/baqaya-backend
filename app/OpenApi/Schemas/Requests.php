<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="OtpRequestBody",
 *     required={"phone"},
 *
 *     @OA\Property(property="phone", type="string", example="+923001234567")
 * )
 *
 * @OA\Schema(
 *     schema="OtpVerifyBody",
 *     required={"phone","code","device_id"},
 *
 *     @OA\Property(property="phone", type="string", example="+923001234567"),
 *     @OA\Property(property="code", type="string", example="123456"),
 *     @OA\Property(property="device_id", type="string", example="pixel-7-arslan"),
 *     @OA\Property(property="device_name", type="string", nullable=true, example="Arslan Phone"),
 *     @OA\Property(property="platform", type="string", nullable=true, example="android"),
 *     @OA\Property(property="fcm_token", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="TokenRefreshBody",
 *     required={"refresh_token","device_id"},
 *
 *     @OA\Property(property="refresh_token", type="string"),
 *     @OA\Property(property="device_id", type="string", example="pixel-7-arslan")
 * )
 *
 * @OA\Schema(
 *     schema="LogoutBody",
 *     required={"device_id"},
 *
 *     @OA\Property(property="device_id", type="string", example="pixel-7-arslan")
 * )
 *
 * @OA\Schema(
 *     schema="CreateTransactionBody",
 *     required={"customer_uuid","amount","transaction_date"},
 *
 *     @OA\Property(property="customer_uuid", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="amount", type="number", format="float", example=1000),
 *     @OA\Property(property="transaction_date", type="string", format="date", example="2026-05-17"),
 *     @OA\Property(property="reference_no", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true, example="Udhar"),
 *     @OA\Property(property="notes", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="TransactionCorrectionBody",
 *     required={"amount","correction_reason"},
 *
 *     @OA\Property(property="amount", type="number", format="float", example=800),
 *     @OA\Property(property="transaction_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="correction_reason", type="string", example="Wrong amount entered"),
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="reference_no", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="TransactionReverseBody",
 *     required={"correction_reason"},
 *
 *     @OA\Property(property="correction_reason", type="string", example="Entered by mistake")
 * )
 *
 * @OA\Schema(
 *     schema="TransactionAdjustmentBody",
 *     required={"customer_uuid","amount","direction","transaction_date"},
 *
 *     @OA\Property(property="customer_uuid", type="string"),
 *     @OA\Property(property="amount", type="number", format="float", example=200),
 *     @OA\Property(property="direction", type="string", enum={"in","out"}, description="in increases balance, out decreases"),
 *     @OA\Property(property="transaction_date", type="string", format="date"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Manual adjustment"),
 *     @OA\Property(property="notes", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="TransactionMetadataBody",
 *
 *     @OA\Property(property="notes", type="string", nullable=true),
 *     @OA\Property(property="reference_no", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="category", type="string", nullable=true),
 *     @OA\Property(property="attachment_path", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="CreatePaymentBody",
 *     required={"customer_uuid","amount","payment_date"},
 *
 *     @OA\Property(property="customer_uuid", type="string"),
 *     @OA\Property(property="amount", type="number", format="float", example=500),
 *     @OA\Property(property="payment_date", type="string", format="date"),
 *     @OA\Property(property="payment_method", type="string", enum={"cash","bank","easypaisa","jazzcash","other"}, example="cash"),
 *     @OA\Property(property="reference_no", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="notes", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="PaymentCorrectionBody",
 *     required={"amount","correction_reason"},
 *
 *     @OA\Property(property="amount", type="number", format="float", example=700),
 *     @OA\Property(property="payment_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="payment_method", type="string", enum={"cash","bank","easypaisa","jazzcash","other"}, nullable=true),
 *     @OA\Property(property="correction_reason", type="string", example="Wrong payment amount"),
 *     @OA\Property(property="notes", type="string", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="SyncPushRequest",
 *     required={"request_id","device_id","operations"},
 *
 *     @OA\Property(property="request_id", type="string", description="Idempotency key for entire batch", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *     @OA\Property(property="device_id", type="string", example="pixel-7-arslan"),
 *     @OA\Property(
 *         property="operations",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *             required={"operation_id","type","payload"},
 *
 *             @OA\Property(property="operation_id", type="string", example="01JXYZABCDEFGHJKMNPQRSTVW"),
 *             @OA\Property(property="type", type="string", example="customer.create"),
 *             @OA\Property(property="payload", type="object")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SyncPushResponse",
 *     type="object",
 *
 *     @OA\Property(property="request_id", type="string"),
 *     @OA\Property(
 *         property="results",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="operation_id", type="string"),
 *             @OA\Property(property="status", type="string", enum={"completed","duplicate","failed"}),
 *             @OA\Property(property="result", type="object", nullable=true),
 *             @OA\Property(property="code", type="string", nullable=true, example="SYNC_CONFLICT"),
 *             @OA\Property(property="message", type="string", nullable=true)
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SyncPullResponse",
 *     type="object",
 *
 *     @OA\Property(property="cursor", type="integer", example=42),
 *     @OA\Property(property="customers", type="array", @OA\Items(ref="#/components/schemas/Customer")),
 *     @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
 *     @OA\Property(property="payments", type="array", @OA\Items(ref="#/components/schemas/Payment")),
 *     @OA\Property(property="reminders", type="array", @OA\Items(ref="#/components/schemas/Reminder"))
 * )
 */
final class Requests {}
