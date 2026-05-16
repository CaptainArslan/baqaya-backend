<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerStatementPdf;
use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateCustomerStatementPdf implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $statementPdfId,
    ) {
        $this->onQueue('pdf');
    }

    public function handle(): void
    {
        $statement = CustomerStatementPdf::query()->findOrFail($this->statementPdfId);
        $customer = Customer::query()->findOrFail($statement->customer_id);
        $shop = Shop::query()->findOrFail($statement->shop_id);

        $transactions = $customer->transactions()
            ->when($statement->from_date, fn ($q) => $q->whereDate('transaction_date', '>=', $statement->from_date))
            ->when($statement->to_date, fn ($q) => $q->whereDate('transaction_date', '<=', $statement->to_date))
            ->orderBy('transaction_date')
            ->get();

        $payments = $customer->payments()
            ->when($statement->from_date, fn ($q) => $q->whereDate('payment_date', '>=', $statement->from_date))
            ->when($statement->to_date, fn ($q) => $q->whereDate('payment_date', '<=', $statement->to_date))
            ->orderBy('payment_date')
            ->get();

        $pdf = Pdf::loadView('pdf.customer-statement', [
            'shop' => $shop,
            'customer' => $customer,
            'transactions' => $transactions,
            'payments' => $payments,
            'fromDate' => $statement->from_date,
            'toDate' => $statement->to_date,
        ]);

        $path = 'statements/'.$statement->uuid.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        $statement->update([
            'file_path' => $path,
            'status' => 'completed',
            'generated_at' => now(),
        ]);
    }
}
