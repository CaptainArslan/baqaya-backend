<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveShop
{
    public function handle(Request $request, Closure $next): mixed
    {
        $shop = $request->user()?->shop;

        if ($shop === null) {
            throw new ApiException('Shop not found.', 'SHOP_NOT_FOUND', Response::HTTP_NOT_FOUND);
        }

        $request->attributes->set('shop', $shop);

        return $next($request);
    }
}
