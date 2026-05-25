<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LedgerEntryResource;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Shop;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashbookController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $todayPayments = (float) Payment::query()
            ->where('shop_id', $shop->id)
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $yesterdayPayments = (float) Payment::query()
            ->where('shop_id', $shop->id)
            ->whereDate('payment_date', $yesterday)
            ->sum('amount');

        $paymentDeltaPercent = $this->deltaPercent($todayPayments, $yesterdayPayments);

        // Ledger entries store signed deltas: credit = +amount, payment = -amount.
        // Net udhaar extended today = SUM(amount) for today's entries (e.g. 1000 + (-500) = 500).
        $todayLedgerNet = (float) LedgerEntry::query()
            ->where('shop_id', $shop->id)
            ->whereDate('created_at', $today)
            ->sum('amount');

        $yesterdayLedgerNet = (float) LedgerEntry::query()
            ->where('shop_id', $shop->id)
            ->whereDate('created_at', $yesterday)
            ->sum('amount');

        $ledgerDeltaPercent = $this->deltaPercent($todayLedgerNet, $yesterdayLedgerNet);

        $todayUdhaarGiven = (float) LedgerEntry::query()
            ->where('shop_id', $shop->id)
            ->whereDate('created_at', $today)
            ->where('entry_type', 'credit')
            ->sum(DB::raw('ABS(amount)'));

        $todayEntriesCount = LedgerEntry::query()
            ->where('shop_id', $shop->id)
            ->whereDate('created_at', $today)
            ->count();

        return ApiResponse::success([
            'today_ledger' => number_format(max($todayLedgerNet, 0), 2, '.', ''),
            'today_ledger_net' => number_format($todayLedgerNet, 2, '.', ''),
            'today_udhaar_given' => number_format($todayUdhaarGiven, 2, '.', ''),
            'yesterday_ledger' => number_format($yesterdayLedgerNet, 2, '.', ''),
            'yesterday_payments' => number_format($yesterdayPayments, 2, '.', ''),
            'total_received' => number_format($todayPayments, 2, '.', ''),
            'collections_count' => Payment::query()
                ->where('shop_id', $shop->id)
                ->whereDate('payment_date', $today)
                ->count(),
            'today_entries_count' => $todayEntriesCount,
            'payment_delta_percent' => $paymentDeltaPercent,
            'ledger_delta_percent' => $ledgerDeltaPercent,
            // Legacy alias
            'delta_percent' => $paymentDeltaPercent,
        ]);
    }

    public function entries(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $query = LedgerEntry::query()
            ->with('customer')
            ->where('shop_id', $shop->id)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->whereHas('customer', fn ($q) => $q->where('name', 'like', $search));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->query('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->query('to_date'));
        }

        $entries = $query->paginate((int) $request->query('per_page', 30));

        return ApiResponse::success(
            LedgerEntryResource::collection($entries),
            meta: [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        );
    }

    private function deltaPercent(float $today, float $yesterday): float
    {
        if ($yesterday == 0.0) {
            return $today > 0 ? 100.0 : 0.0;
        }

        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }
}
