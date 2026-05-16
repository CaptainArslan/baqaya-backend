<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditActionType;
use App\Exceptions\ApiException;
use App\Models\Customer;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

class CustomerService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function list(Shop $shop, ?string $search = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($shop, $search)
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Shop $shop, User $user, array $data, bool $syncOrigin = false): Customer
    {
        $this->assertUniqueName($shop, $data['name']);
        $this->assertUniquePhone($shop, $data['phone'] ?? null);

        $customer = Customer::query()->create([
            'shop_id' => $shop->id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'current_balance' => $data['opening_balance'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'client_created_at' => $data['client_created_at'] ?? null,
            'client_updated_at' => $data['client_updated_at'] ?? null,
        ]);

        $this->auditService->log(
            AuditActionType::Created,
            Customer::class,
            $customer->uuid,
            $shop,
            $user,
            syncOrigin: $syncOrigin,
        );

        return $customer;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<Customer>
     */
    public function bulkCreate(Shop $shop, User $user, array $rows): array
    {
        $created = [];

        foreach ($rows as $row) {
            $created[] = $this->create($shop, $user, $row);
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Shop $shop, Customer $customer, User $user, array $data, bool $syncOrigin = false): Customer
    {
        if ($customer->shop_id !== $shop->id) {
            throw new ApiException('Customer not found.', 'NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        if (isset($data['name']) && $data['name'] !== $customer->name) {
            $this->assertUniqueName($shop, $data['name'], $customer->id);
        }

        if (isset($data['phone']) && $data['phone'] !== $customer->phone) {
            $this->assertUniquePhone($shop, $data['phone'], $customer->id);
        }

        $old = $customer->toArray();
        $customer->update($data);

        $this->auditService->log(
            AuditActionType::Updated,
            Customer::class,
            $customer->uuid,
            $shop,
            $user,
            oldValues: $old,
            newValues: $customer->fresh()->toArray(),
            syncOrigin: $syncOrigin,
        );

        return $customer->fresh();
    }

    public function delete(Shop $shop, Customer $customer, User $user, bool $syncOrigin = false): void
    {
        if ($customer->shop_id !== $shop->id) {
            throw new ApiException('Customer not found.', 'NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $customer->delete();

        $this->auditService->log(
            AuditActionType::Deleted,
            Customer::class,
            $customer->uuid,
            $shop,
            $user,
            syncOrigin: $syncOrigin,
        );
    }

    public function findByUuid(Shop $shop, string $uuid): Customer
    {
        $customer = Customer::query()
            ->where('shop_id', $shop->id)
            ->where('uuid', $uuid)
            ->first();

        if ($customer === null) {
            throw new ApiException('Customer not found.', 'NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        return $customer;
    }

    private function query(Shop $shop, ?string $search): Builder
    {
        $query = Customer::query()->where('shop_id', $shop->id);

        if ($search !== null && $search !== '') {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function assertUniqueName(Shop $shop, string $name, ?int $exceptId = null): void
    {
        $exists = Customer::query()
            ->where('shop_id', $shop->id)
            ->where('name', $name)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            throw new ApiException(
                'A customer with this name already exists in this shop.',
                'DUPLICATE_CUSTOMER_NAME',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }

    private function assertUniquePhone(Shop $shop, ?string $phone, ?int $exceptId = null): void
    {
        if ($phone === null || $phone === '') {
            return;
        }

        $exists = Customer::query()
            ->where('shop_id', $shop->id)
            ->where('phone', $phone)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            throw new ApiException('Phone already exists for this shop.', 'DUPLICATE_PHONE', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
