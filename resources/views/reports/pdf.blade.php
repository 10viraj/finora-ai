<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Statement - {{ $monthName }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .title {
            font-size: 22px;
            font-weight: bold;
            color: #2563eb;
            margin: 0;
        }
        .subtitle {
            font-size: 11px;
            color: #64748b;
        }
        .period-box {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
        }
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .kpi-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            text-align: center;
            width: 25%;
        }
        .kpi-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 18px;
            font-weight: bold;
        }
        .text-green { color: #16a34a; }
        .text-red { color: #dc2626; }
        .text-blue { color: #2563eb; }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding: 8px 6px;
            text-align: left;
        }
        .data-table td {
            padding: 7px 6px;
            border-bottom: 1px solid #f1f5f9;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="title">{{ $user->business_name ?: $user->name }}</div>
                    <div class="subtitle">FinTrack AI Monthly Executive Financial Statement</div>
                </td>
                <td class="period-box">
                    Statement Period:<br>
                    <span style="color: #2563eb;">{{ $monthName }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- KPI Summary Row -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-box">
                <div class="kpi-label">Total Inflows</div>
                <div class="kpi-value text-green">+${{ number_format($income, 2) }}</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-label">Total Outflows</div>
                <div class="kpi-value text-red">-${{ number_format($expense, 2) }}</div>
            </td>
            <td class="kpi-box">
                <div class="kpi-label">Net Surplus</div>
                <div class="kpi-value {{ $savings >= 0 ? 'text-blue' : 'text-red' }}">
                    {{ $savings >= 0 ? '+' : '' }}${{ number_format($savings, 2) }}
                </div>
            </td>
            <td class="kpi-box">
                <div class="kpi-label">Savings Rate</div>
                <div class="kpi-value">{{ $savingsRate }}%</div>
            </td>
        </tr>
    </table>

    <!-- Category Breakdown Table -->
    <div class="section-title">Expense Summary by Category</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Category</th>
                <th class="text-right">Total Expended</th>
                <th class="text-right">Share of Outflows</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categorySpending as $cat)
                @php
                    $share = $expense > 0 ? round(($cat->total / $expense) * 100, 1) : 0;
                @endphp
                <tr>
                    <td>{{ $cat->name }}</td>
                    <td class="text-right">${{ number_format($cat->total, 2) }}</td>
                    <td class="text-right">{{ $share }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Transactions Log Table -->
    <div class="section-title">Itemized Transactions for {{ $monthName }}</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Account</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
                <tr>
                    <td>{{ $tx->transaction_date ? $tx->transaction_date->format('M d, Y') : '-' }}</td>
                    <td>{{ $tx->description }}</td>
                    <td>{{ $tx->category->name ?? 'Transfer / Other' }}</td>
                    <td>{{ $tx->account->name ?? 'Default' }}</td>
                    <td class="text-right {{ $tx->type === 'income' ? 'text-green' : 'text-red' }}">
                        {{ $tx->type === 'income' ? '+' : '-' }}${{ number_format($tx->amount, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated automatically by FinTrack AI &bull; Confidential Financial Record for {{ $user->name }} &bull; {{ date('Y-m-d H:i:s') }}
    </div>

</body>
</html>
