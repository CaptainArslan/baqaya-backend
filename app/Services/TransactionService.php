<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditActionType;
use App\Enums\FinancialRecordStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransactionType;
use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionService
{
    public function __construct(
        private readonly BalanceService $balanceService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCredit(
        Shop $shop,
        Customer $customer,
        User $user,
        array $data,
        ?UserDevice $device = null,
        ?string $syncOperationId = null,
        bool $syncOrigin = false,
        ?Request $request = null,
    ): Transaction {
        return $this->balanceService->withCustomerLock($customer, function (Customer $lockedCustomer) use (
            $shop, $user, $data, $device, $syncOperationId, $syncOrigin, $request
        ) {
            $amount = (string) $data['amount'];
            $direction = TransactionDirection::In;

            $transaction = Transaction::query()->create([
                'shop_id' => $shop->id,
                'customer_id' => $lockedCustomer->id,
                'type' => TransactionType::Credit,
                'status' => FinancialRecordStatus::Active,
                'amount' => $amount,
                'direction' => $direction,
                'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'balance_after' => 0,
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
                'credit',
                $this->balanceService->creditDelta($amount),
                $transaction,
                $syncOperationId,
            );

            $transaction->update(['balance_after' => $lockedCustomer->current_balance]);

            $this->auditService->log(
                AuditActionType::Created,
                Transaction::class,
                $transaction->uuid,
                $shop,
                $user,
                $device,
                null,
                $transaction->toArray(),
                $syncOrigin,
                $request,
            );

            return $transaction->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAdjustment(
        Shop $shop,
        Customer $customer,
        User $user,
        array $data,
        ?UserDevice $device = null,
        ?string $syncOperationId = null,
        bool $syncOrigin = false,
        ?Request $request = null,
    ): Transaction {
        return $this->balanceService->withCustomerLock($customer, function (Customer $lockedCustomer) use (
            $shop, $user, $data, $device, $syncOperationId, $syncOrigin, $request
        ) {
            $amount = (string) $data['amount'];
            $direction = TransactionDirection::from($data['direction']);

            $transaction = Transaction::query()->create([
                'shop_id' => $shop->id,
                'customer_id' => $lockedCustomer->id,
                'type' => TransactionType::Adjustment,
                'status' => FinancialRecordStatus::Active,
                'amount' => $amount,
                'direction' => $direction,
                'description' => $data['description'] ?? 'Balance adjustment',
                'notes' => $data['notes'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'balance_after' => 0,
                'created_by' => $user->id,
                'device_id' => $device?->id,
                'sync_operation_id' => $syncOperationId,
            ]);

            $delta = $direction === TransactionDirection::In
                ? $this->balanceService->creditDelta($amount)
                : $this->balanceService->paymentDelta($amount);

            $lockedCustomer = $this->balanceService->applyDelta(
                $lockedCustomer,
                $shop,
                $user,
                'adjustment',
                $delta,
                $transaction,
                $syncOperationId,
            );

            $transaction->update(['balance_after' => $lockedCustomer->current_balance]);

            $this->auditService->log(
                AuditActionType::Created,
                Transaction::class,
                $transaction->uuid,
                $shop,
                $user,
                $device,
                null,
                $transaction->toArray(),
                $syncOrigin,
                $request,
            );

            return $transaction->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function correct(
        Shop $shop,
        Transaction $original,
        User $user,
        array $data,
        ?UserDevice $device = null,
        ?string $syncOperationId = null,
        bool $syncOrigin = false,
        ?Request $request = null,
    ): Transaction {
        if ($original->status !== FinancialRecordStatus::Active) {
            throw new ApiException('Transaction cannot be corrected.', 'INVALID_STATUS', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($original->shop_id !== $shop->id) {
            throw new ApiException('Transaction not found.', 'NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $customer = $original->customer;

        return $this->balanceService->withCustomerLock($customer, function (Customer $lockedCustomer) use (
            $shop, $original, $user, $data, $device, $syncOperationId, $syncOrigin, $request
        ) {
            $reversal = $this->createReversalInternal($shop, $original, $lockedCustomer, $user, $data['correction_reason'], $device, $syncOperationId);

            $corrected = Transaction::query()->create([
                'shop_id' => $shop->id,
                'customer_id' => $lockedCustomer->id,
                'type' => TransactionType::Credit,
                'status' => FinancialRecordStatus::Active,
                'amount' => (string) $data['amount'],
                'direction' => TransactionDirection::In,
                'reference_no' => $data['reference_no'] ?? $original->reference_no,
                'description' => $data['description'] ?? $original->description,
                'notes' => $data['notes'] ?? $original->notes,
                'transaction_date' => $data['transaction_date'] ?? $original->transaction_date,
                'balance_after' => 0,
                'corrected_by_transaction_id' => null,
                'created_by' => $user->id,
                'device_id' => $device?->id,
                'sync_operation_id' => $syncOperationId,
            ]);

            $lockedCustomer = $this->balanceService->applyDelta(
                $lockedCustomer,
                $shop,
                $user,
                'credit_correction',
                $this->balanceService->creditDelta((string) $data['amount']),
                $corrected,
                $syncOperationId,
            );

            $corrected->update(['balance_after' => $lockedCustomer->current_balance]);

            $original->update([
                'status' => FinancialRecordStatus::Corrected,
                'corrected_by_transaction_id' => $corrected->id,
                'correction_reason' => $data['correction_reason'],
                'corrected_at' => now(),
                'corrected_by' => $user->id,
            ]);

            $this->auditService->log(
                AuditActionType::Corrected,
                Transaction::class,
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
        Transaction $original,
        User $user,
        string $reason,
        ?UserDevice $device = null,
        ?string $syncOperationId = null,
        bool $syncOrigin = false,
        ?Request $request = null,
    ): Transaction {
        if ($original->status !== FinancialRecordStatus::Active) {
            throw new ApiException('Transaction cannot be reversed.', 'INVALID_STATUS', Response::HTTP_UNPROCESSABLE_ENTITY);
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
                Transaction::class,
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
        Transaction $transaction,
        User $user,
        array $data,
        ?UserDevice $device = null,
        ?Request $request = null,
    ): Transaction {
        if ($transaction->shop_id !== $shop->id) {
            throw new ApiException('Transaction not found.', 'NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $old = $transaction->only(['notes', 'reference_no', 'description', 'category', 'attachment_path']);

        $transaction->update(array_intersect_key($data, array_flip([
            'notes', 'reference_no', 'description', 'category', 'attachment_path',
        ])));

        $this->auditService->log(
            AuditActionType::Updated,
            Transaction::class,
            $transaction->uuid,
            $shop,
            $user,
            $device,
            $old,
            $transaction->only(['notes', 'reference_no', 'description', 'category', 'attachment_path']),
            false,
            $request,
        );

        return $transaction->fresh();
    }

    private function createReversalInternal(
        Shop $shop,
        Transaction $original,
        Customer $customer,
        User $user,
        string $reason,
        ?UserDevice $device,
        ?string $syncOperationId,
    ): Transaction {
        $reversalAmount = (string) $original->amount;

        $reversal = Transaction::query()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'type' => TransactionType::Adjustment,
            'status' => FinancialRecordStatus::Active,
            'amount' => $reversalAmount,
            'direction' => TransactionDirection::Out,
            'description' => 'Reversal: '.$reason,
            'transaction_date' => now()->toDateString(),
            'balance_after' => 0,
            'reversal_of_transaction_id' => $original->id,
            'created_by' => $user->id,
            'device_id' => $device?->id,
            'sync_operation_id' => $syncOperationId,
        ]);

        $customer = $this->balanceService->applyDelta(
            $customer,
            $shop,
            $user,
            'transaction_reversal',
            $this->balanceService->paymentDelta($reversalAmount),
            $reversal,
            $syncOperationId,
        );

        $reversal->update(['balance_after' => $customer->current_balance]);

        return $reversal;
    }
}
