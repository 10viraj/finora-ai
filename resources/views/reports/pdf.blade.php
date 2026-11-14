<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $label }} - {{ $formatted_date }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 24px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header table {
            width: 100%;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin: 0;
        }
        .subtitle {
            font-size: 10px;
            color: #64748b;
        }
        .period-box {
            text-align: right;
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .period-sub {
            font-size: 10px;
            color: #64748b;
            font-weight: normal;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .kpi-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px;
            text-align: center;
            width: 25%;
        }
        .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .kpi-value {
            font-size: 16px;
            font-weight: bold;
        }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-blue { color: #2563eb; }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 18px;
            margin-bottom: 8px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 5px;
            text-align: left;
        }
        .data-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
        }
        .text-right { text-align: right; }
        .footer {
            margin-top: 24px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 9px;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-income { background-color: #dcfce7; color: #15803d; }
        .badge-expense { background-color: #fee2e2; color: #b91c1c; }
        .badge-transfer { background-color: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1 class="title">FinTrack<span style="color: #0f172a;">.AI</span></h1>
                    <div class="subtitle">
                        {{ $user->business_name ? $user->business_name . ' • ' : '' }}Official Financial Statement & Analytics
                    </div>
                </td>
                <td class="period-box">
                    <div>{{ $label }}</div>
                    <div class="period-sub">{{ $formatted_date }}</div>
                    <div class="period-sub" style="font-size: 8px;">Date Range: {{ $start_date }} to {{ $end_date }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary KPI Boxes -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-box">
                <div class="kpi-label">Total Inflows / Revenue</div>
                <div class="kpi-value text-green">${{ number_format($income, 2) }}</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-label">Total Outflows / Expenses</div>
                <div class="kpi-value text-red">${{ number_format($expense, 2) }}</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-label">Net Savings</div>
                <div class="kpi-value {{ $savings >= 0 ? 'text-blue' : 'text-red' }}">
                    {{ $savings >= 0 ? '+' : '' }}${{ number_format($savings, 2) }}
                </div>
            </td>
            <td class="kpi-box">
                <div class="kpi-label">Savings Retention</div>
                <div class="kpi-value {{ $savings_rate >= 0 ? 'text-green' : 'text-red' }}">
                    {{ $savings_rate }}%
                </div>
            </td>
        </tr>
    </table>

    <!-- Savings & Performance Health -->
    <div class="section-title">1. Savings Performance & Wealth Accumulation</div>
    <table class="data-table">
        <tr>
            <td style="width: 25%;"><strong>Net Savings:</strong></td>
            <td style="width: 25%;">${{ number_format($savings, 2) }}</td>
            <td style="width: 25%;"><strong>Status Rating:</strong></td>
            <td style="width: 25%;">{{ $savings_analysis['status_label'] }}</td>
        </tr>
        <tr>
            <td><strong>Benchmark Target:</strong></td>
            <td>{{ $savings_analysis['benchmark_target'] }}%</td>
            <td><strong>Variance vs Target:</strong></td>
            <td class="{{ $savings_analysis['variance_from_benchmark'] >= 0 ? 'text-green' : 'text-red' }}">
                {{ $savings_analysis['variance_from_benchmark'] >= 0 ? '+' : '' }}{{ $savings_analysis['variance_from_benchmark'] }}%
            </td>
        </tr>
        <tr>
            <td><strong>Daily Avg Spend:</strong></td>
            <td>${{ number_format($daily_avg_spend, 2) }}/day</td>
            <td><strong>Projected Annual:</strong></td>
            <td>${{ number_format($savings_analysis['projected_annual_savings'], 2) }}</td>
        </tr>
    </table>

    <!-- Category-Wise Expenses -->
    <div class="section-title">2. Category-Wise Expenses Breakdown</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th class="text-right">Transaction Count</th>
                <th class="text-right">Avg / Transaction</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Share of Outflows</th>
            </tr>
        </thead>
        <tbody>
            @forelse($category_breakdown as $cat)
                <tr>
                    <td><strong>{{ $cat['name'] }}</strong></td>
                    <td class="text-right">{{ $cat['transaction_count'] }}</td>
                    <td class="text-right">${{ number_format($cat['average_transaction'], 2) }}</td>
                    <td class="text-right"><strong>${{ number_format($cat['total_spent'], 2) }}</strong></td>
                    <td class="text-right">{{ $cat['share_percentage'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 10px;">
                        No expense records found for this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="3">TOTAL EXPENSES</td>
                <td class="text-right text-red">${{ number_format($expense, 2) }}</td>
                <td class="text-right">100.0%</td>
            </tr>
        </tfoot>
    </table>

    <!-- Transaction Register -->
    <div class="section-title">3. Transaction Register ({{ count($transactions) }} Records)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Description</th>
                <th>Account</th>
                <th>Payment</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions->take(50) as $tx)
                <tr>
                    <td>{{ $tx->transaction_date->format('Y-m-d') }}</td>
                    <td>
                        <span class="badge {{ $tx->type === 'income' ? 'badge-income' : ($tx->type === 'expense' ? 'badge-expense' : 'badge-transfer') }}">
                            {{ $tx->type }}
                        </span>
                    </td>
                    <td>{{ $tx->category->name ?? '-' }}</td>
                    <td>{{ $tx->description }}</td>
                    <td>{{ $tx->account->name ?? '-' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $tx->payment_method)) }}</td>
                    <td class="text-right {{ $tx->type === 'income' ? 'text-green' : ($tx->type === 'expense' ? 'text-red' : '') }}" style="font-weight: bold;">
                        {{ $tx->type === 'income' ? '+' : ($tx->type === 'expense' ? '-' : '') }}${{ number_format($tx->amount, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 10px;">
                        No transactions recorded for this period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        Generated by FinTrack AI Statement Engine • User ID: {{ $user->id }} ({{ $user->email }}) • {{ now()->format('Y-m-d H:i:s T') }}
    </div>

</body>
</html>
