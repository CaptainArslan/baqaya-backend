<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Statement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h1>{{ $shop->name }} — Statement</h1>
    <p><strong>Customer:</strong> {{ $customer->name }}</p>
    <p><strong>Balance:</strong> {{ $customer->current_balance }} {{ $shop->currency }}</p>
    @if($fromDate || $toDate)
        <p><strong>Period:</strong> {{ $fromDate ?? '—' }} to {{ $toDate ?? '—' }}</p>
    @endif

    <h2>Transactions</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance After</th></tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
                <tr>
                    <td>{{ $tx->transaction_date->format('Y-m-d') }}</td>
                    <td>{{ $tx->type->value }}</td>
                    <td>{{ $tx->amount }}</td>
                    <td>{{ $tx->balance_after }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Payments</h2>
    <table>
        <thead>
            <tr><th>Date</th><th>Method</th><th>Amount</th><th>Balance After</th></tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                    <td>{{ $payment->payment_method->value }}</td>
                    <td>{{ $payment->amount }}</td>
                    <td>{{ $payment->balance_after }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
