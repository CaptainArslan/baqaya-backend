<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditActionType;
use App\Exceptions\ApiException;
use App\Models\Shop;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class ShopService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function findForUser(User $user): Shop
    {
        $shop = $user->shop;

        if ($shop === null) {
            throw new ApiException('Shop not found.', 'SHOP_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        return $shop;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Shop
    {
        if ($user->shop()->exists()) {
            throw new ApiException(
                'This account already has a shop. Each user may own only one shop.',
                'SHOP_ALREADY_EXISTS',
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $shop = Shop::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'business_type' => $data['business_type'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'currency' => $data['currency'] ?? 'PKR',
            'timezone' => $data['timezone'] ?? 'Asia/Karachi',
            'terms_accepted_at' => $data['terms_accepted_at'] ?? now(),
        ]);

        $this->auditService->log(
            AuditActionType::Created,
            Shop::class,
            $shop->uuid,
            $shop,
            $user,
        );

        return $shop;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Shop $shop, User $user, array $data): Shop
    {
        $old = $shop->toArray();
        $shop->update($data);
        $shop->increment('server_version');

        $this->auditService->log(
            AuditActionType::Updated,
            Shop::class,
            $shop->uuid,
            $shop,
            $user,
            oldValues: $old,
            newValues: $shop->fresh()->toArray(),
        );

        return $shop->fresh();
    }

    public function delete(Shop $shop, User $user): void
    {
        $shop->delete();

        $this->auditService->log(
            AuditActionType::Deleted,
            Shop::class,
            $shop->uuid,
            $shop,
            $user,
        );
    }
}
