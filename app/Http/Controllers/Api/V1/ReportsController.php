<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\Transaction;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportsController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['sometimes', Rule::in(['today', 'week', 'month'])],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
        ]);
        $period = $validated['period'] ?? 'month';
        [$rangeFrom, $rangeTo] = $this->resolveDateRange($validated, $period);

        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $debtors = Customer::query()
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->where('current_balance', '>', 0)
            ->orderByDesc('current_balance')
            ->limit(5)
            ->get();

        $totalToCollect = Customer::query()
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->where('current_balance', '>', 0)
            ->sum('current_balance');

        $pendingCount = Customer::query()
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->where('current_balance', '>', 0)
            ->count();

        $today = now()->toDateString();
        $todayCollections = Payment::query()
            ->where('shop_id', $shop->id)
            ->whereDate('payment_date', $today)
            ->sum('amount');

        $monthCollections = Payment::query()
            ->where('shop_id', $shop->id)
            ->whereBetween('payment_date', [now()->startOfMonth()->toDateString(), $today])
            ->sum('amount');

        $latePayers = Customer::query()
            ->where('shop_id', $shop->id)
            ->where('is_active', true)
            ->where('current_balance', '>', 0)
            ->where('updated_at', '<', now()->subDays(7))
            ->orderBy('updated_at')
            ->limit(10)
            ->get()
            ->map(fn (Customer $c) => [
                'uuid' => $c->uuid,
                'name' => $c->name,
                'days_pending' => (int) $c->updated_at?->diffInDays(now()),
            ]);

        $topDebtors = $debtors->map(function (Customer $c) use ($shop) {
            $txCount = Transaction::query()
                ->where('shop_id', $shop->id)
                ->where('customer_id', $c->id)
                ->count();

            return [
                'uuid' => $c->uuid,
                'name' => $c->name,
                'tx_count' => $txCount,
                'balance' => $c->current_balance,
            ];
        });

        $weekCollections = $this->sumPaymentsBetween(
            $shop,
            now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            $today,
        );

        $periodCollections = $this->sumPaymentsBetween($shop, $rangeFrom, $rangeTo);

        return ApiResponse::success([
            'total_to_collect' => number_format((float) $totalToCollect, 2, '.', ''),
            'pending_customers_count' => $pendingCount,
            'today_collections' => number_format((float) $todayCollections, 2, '.', ''),
            'week_collections' => number_format((float) $weekCollections, 2, '.', ''),
            'month_collections' => number_format((float) $monthCollections, 2, '.', ''),
            'period_collections' => number_format((float) $periodCollections, 2, '.', ''),
            'range_from' => $rangeFrom,
            'range_to' => $rangeTo,
            'weekly_collections' => $this->weeklyBucketsForDateRange($shop, $rangeFrom, $rangeTo),
            'late_payers' => $latePayers,
            'top_debtors' => $topDebtors,
        ]);
    }

    /**
     * @param  array{period?: string, from?: string, to?: string}  $validated
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(array $validated, string $period): array
    {
        $today = now()->toDateString();

        if (! empty($validated['from']) && ! empty($validated['to'])) {
            $to = min($validated['to'], $today);

            return [$validated['from'], $to];
        }

        return match ($period) {
            'today' => [$today, $today],
            'week' => [now()->subDays(6)->toDateString(), $today],
            default => [now()->startOfMonth()->toDateString(), $today],
        };
    }

    /**
     * Split any date range into four chart buckets (W1–W4).
     *
     * @return list<array{week: int, label: string, amount: string}>
     */
    private function weeklyBucketsForDateRange(Shop $shop, string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        if ($end->lt($start)) {
            return $this->formatWeeklyBuckets([0.0, 0.0, 0.0, 0.0]);
        }

        $totalDays = (int) $start->diffInDays($end) + 1;
        $segmentSize = max(1, (int) ceil($totalDays / 4));
        $amounts = [];

        for ($i = 0; $i < 4; $i++) {
            $segStart = $start->copy()->addDays($i * $segmentSize);
            if ($segStart->gt($end)) {
                $amounts[] = 0.0;
                continue;
            }
            $segEnd = $start->copy()->addDays(min(($i + 1) * $segmentSize - 1, $totalDays - 1));
            if ($segEnd->gt($end)) {
                $segEnd = $end->copy();
            }
            $amounts[] = $this->sumPaymentsBetween(
                $shop,
                $segStart->toDateString(),
                $segEnd->toDateString(),
            );
        }

        return $this->formatWeeklyBuckets($amounts);
    }

    /**
     * @param  list<float>  $amounts
     * @return list<array{week: int, label: string, amount: string}>
     */
    private function formatWeeklyBuckets(array $amounts): array
    {
        $buckets = [];
        foreach (array_values($amounts) as $index => $amount) {
            $week = $index + 1;
            $buckets[] = [
                'week' => $week,
                'label' => 'W'.$week,
                'amount' => number_format($amount, 2, '.', ''),
            ];
        }

        return $buckets;
    }

    private function sumPaymentsBetween(Shop $shop, string $from, string $to): float
    {
        return (float) Payment::query()
            ->where('shop_id', $shop->id)
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount');
    }
}
