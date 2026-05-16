<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditActionType;
use App\Enums\FinancialRecordStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentService
{
    public function __construct(
        private readonly BalanceService $balanceService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        Shop $shop,
        Customer $customer,
        User $user,
        array $data,
        ?UserDevice $device = null,
        ?string $syncOperationId = null,
        bool $syncOrigin = false,
        ?Request $request = null,
    ): Payment {
        return $this->balanceService->withCustomerLock($customer, function (Customer $lockedCustomer) use (
            $shop, $user, $data, $device, $syncOperationId, $syncOrigin, $request
        ) {
            $amount = (string) $data['amount'];

            $payment = Payment::query()->create([
                'shop_id' => $shop->id,
                'customer_id' => $lockedCustomer->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? PaymentMethod::Cash,
                'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'payment_date' => $data['payment_date'],
                'balance_after' => 0,
                'status' => FinancialRecordStatus::Active,
                'created_by' => $user->id,
                'device_id' => $device?->id,
                'sync_operation_id' => $syncOperationId,
                'client_created_at' => $data['client_created_at'] ?? null,
                'client_updated_at' => $data['client_updated_at'] ?? null,
            ]);

            $lockedCustomer = $this->balanceService->applyDelta(
                $lockedCustomer,
                $shop,
                $user,
                'payment',
                $this->balanceService->paymentDelta($amount),
                $payment,
                $syncOperationId,
            );

            $payment->update(['balance_after' => $lockedCustomer->current_balance]);

            $this->auditService->log(
                AuditActionType::Payment,
                Payment::class,
                $payment->uuid,
                $shop,
                $user,
                $device,
                null,
                $payment->toArray(),
                $syncOrigin,
                $request,
            );

            return $payment->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function correct(
        Shop $shop,
        Payment $original,
        User $user,
        array $data,
        ?UserDevice $device = null,
        ?string $syncOperationId = null,
        bool $syncOrigin = false,
        ?Request $request = null,
    ): Payment {
        if ($original->status !== FinancialRecordStatus::Active) {
            throw new ApiException('Payment cannot be corrected.', 'INVALID_STATUS', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($original->shop_id !== $shop->id) {
            throw new ApiException('Payment not found.', 'NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        return $this->balanceService->withCustomerLock($original->customer, function (Customer $lockedCustomer) use (
            $shop, $original, $user, $data, $device, $syncOperationId, $syncOrigin, $request
        ) {
            $this->createReversalInternal($shop, $original, $lockedCustomer, $user, $data['correction_reason'], $device, $syncOperationId);

            $corrected = Payment::query()->create([
                'shop_id' => $shop->id,
                'customer_id' => $lockedCustomer->id,
                'amount' => (string) $data['amount'],
                'payment_method' => $data['payment_method'] ?? $original->payment_method,
                'reference_no' => $data['reference_no'] ?? $original->reference_no,
                'description' => $data['description'] ?? $original->description,
                'notes' => $data['notes'] ?? $original->notes,
                'payment_date' => $data['payment_date'] ?? $original->payment_date,
                'balance_after' => 0,
                'status' => FinancialRecordStatus::Active,
                'created_by' => $user->id,
                'device_id' => $device?->id,
                'sync_operation_id' => $syncOperationId,
            ]);

            $lockedCustomer = $this->balanceService->applyDelta(
                $lockedCustomer,
                $shop,
                $user,
                'payment_correction',
                $this->balanceService->paymentDelta((string) $data['amount']),
                $corrected,
                $syncOperationId,
            );

            $corrected->update(['balance_after' => $lockedCustomer->current_balance]);

            $original->update([
                'status' => FinancialRecordStatus::Corrected,
                'corrected_by_payment_id' => $corrected->id,
                'correction_reason' => $data['correction_reason'],
                'corrected_at' => now(),
                'corrected_by' => $user->id,
            ]);

            $this->auditService->log(
                AuditActionType::Corrected,
                Payment::class,
                $corrected->uuid,
                $shop,
                $user,
                $device,
                ['original_uuid' => $original->uuid],
                $corrected->toArray(),
                $syncOrigin,
                $request,
            );

            return $corrected->fresh();
        });
    }

    public function reverse(
        Shop $shop,
        Payment $original,
        User $user,
        string $reason,
        ?UserDevice $device = null,
        ?string $syncOperationId = null,
        bool $syncOrigin = false,
        ?Request $request = null,
    ): Payment {
        if ($original->status !== FinancialRecordStatus::Active) {
            throw new ApiException('Payment cannot be reversed.', 'INVALID_STATUS', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->balanceService->withCustomerLock($original->customer, function (Customer $lockedCustomer) use (
            $shop, $original, $user, $reason, $device, $syncOperationId, $syncOrigin, $request
        ) {
            $reversal = $this->createReversalInternal($shop, $original, $lockedCustomer, $user, $reason, $device, $syncOperationId);

            $original->update([
                'status' => FinancialRecordStatus::Reversed,
                'correction_reason' => $reason,
                'corrected_at' => now(),
                'corrected_by' => $user->id,
            ]);

            $this->auditService->log(
                AuditActionType::Reversed,
                Payment::class,
                $reversal->uuid,
                $shop,
                $user,
                $device,
                ['original_uuid' => $original->uuid],
                $reversal->toArray(),
                $syncOrigin,
                $request,
            );

            return $reversal->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMetadata(
        Shop $shop,
        Payment $payment,
        User $user,
        array $data,
        ?UserDevice $device = null,
        ?Request $request = null,
    ): Payment {
        if ($payment->shop_id !== $shop->id) {
            throw new ApiException('Payment not found.', 'NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $old = $payment->only(['notes', 'reference_no', 'description', 'category', 'attachment_path']);

        $payment->update(array_intersect_key($data, array_flip([
            'notes', 'reference_no', 'description', 'category', 'attachment_path',
        ])));

        $this->auditService->log(
            AuditActionType::Updated,
            Payment::class,
            $payment->uuid,
            $shop,
            $user,
            $device,
            $old,
            $payment->only(['notes', 'reference_no', 'description', 'category', 'attachment_path']),
            false,
            $request,
        );

        return $payment->fresh();
    }

    private function createReversalInternal(
        Shop $shop,
        Payment $original,
        Customer $customer,
        User $user,
        string $reason,
        ?UserDevice $device,
        ?string $syncOperationId,
    ): Payment {
        $reversalAmount = (string) $original->amount;

        $reversal = Payment::query()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'amount' => $reversalAmount,
            'payment_method' => $original->payment_method,
            'description' => 'Reversal: '.$reason,
            'payment_date' => now()->toDateString(),
            'balance_after' => 0,
            'status' => FinancialRecordStatus::Active,
            'reversal_of_payment_id' => $original->id,
            'created_by' => $user->id,
            'device_id' => $device?->id,
            'sync_operation_id' => $syncOperationId,
        ]);

        $customer = $this->balanceService->applyDelta(
            $customer,
            $shop,
            $user,
            'payment_reversal',
            $this->balanceService->creditDelta($reversalAmount),
            $reversal,
            $syncOperationId,
        );

        $reversal->update(['balance_after' => $customer->current_balance]);

        return $reversal;
    }
}
