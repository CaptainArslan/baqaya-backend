<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\LedgerEntry;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  callable(): mixed  $callback
     */
    public function withCustomerLock(Customer $customer, callable $callback): mixed
    {
        $lock = Cache::lock('customer-balance:'.$customer->uuid, 30);

        return $lock->block(10, function () use ($customer, $callback) {
            return DB::transaction(function () use ($customer, $callback) {
                $customer->refresh();

                return $callback($customer);
            });
        });
    }

    public function applyDelta(
        Customer $customer,
        Shop $shop,
        User $user,
        string $entryType,
        string $amount,
        Model $source,
        ?string $syncOperationId = null,
    ): Customer {
        $balanceBefore = (string) $customer->current_balance;
        $balanceAfter = bcadd($balanceBefore, $amount, 2);

        LedgerEntry::query()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'entry_type' => $entryType,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->id,
            'created_by' => $user->id,
            'sync_operation_id' => $syncOperationId,
        ]);

        $customer->update(['current_balance' => $balanceAfter]);

        return $customer->fresh();
    }

    public function creditDelta(string $amount): string
    {
        return $amount;
    }

    public function paymentDelta(string $amount): string
    {
        return bcsub('0', $amount, 2);
    }

    public function reverseDelta(string $originalAmount, bool $wasCredit): string
    {
        return $wasCredit ? $this->paymentDelta($originalAmount) : $this->creditDelta($originalAmount);
    }
}
