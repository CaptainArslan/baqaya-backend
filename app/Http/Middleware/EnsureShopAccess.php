<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var Shop|null $shop */
        $shop = $request->attributes->get('shop');
        $user = $request->user();

        if ($shop === null || $user === null) {
            throw new ApiException('Unauthorized shop access.', 'SHOP_ACCESS_DENIED', Response::HTTP_FORBIDDEN);
        }

        if ($shop->user_id !== $user->id) {
            throw new ApiException('Unauthorized shop access.', 'SHOP_ACCESS_DENIED', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
