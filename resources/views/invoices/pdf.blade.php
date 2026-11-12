<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            font-size: 13px;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header table {
            width: 100%;
        }
        .brand-title {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin: 0;
        }
        .brand-subtitle {
            font-size: 11px;
            color: #64748b;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            color: #1e293b;
            margin: 0;
        }
        .invoice-meta {
            text-align: right;
            font-size: 12px;
            color: #64748b;
        }
        .info-table {
            width: 100%;
            margin-bottom: 25px;
        }
        .info-col {
            width: 50%;
            vertical-align: top;
        }
        .section-label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
            padding: 10px 8px;
            text-align: left;
        }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .text-right {
            text-align: right;
        }
        .totals-table {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .totals-table td {
            padding: 6px 8px;
        }
        .totals-table .grand-total {
            border-top: 2px solid #2563eb;
            font-size: 15px;
            font-weight: bold;
            color: #2563eb;
        }
        .notes-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 12px;
            font-size: 12px;
            color: #475569;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 3px;
            background-color: #e2e8f0;
            color: #334155;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="brand-title">{{ $invoice->user->business_name ?: $invoice->user->name }}</div>
                    <div class="brand-subtitle">{{ $invoice->user->email }} | {{ $invoice->user->phone ?? 'FinTrack AI Verified' }}</div>
                </td>
                <td>
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-meta">
                        <strong>Invoice #:</strong> {{ $invoice->invoice_number }}<br>
                        <strong>Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}<br>
                        <strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}<br>
                        <strong>Status:</strong> <span class="status-badge">{{ strtoupper($invoice->status) }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-col">
                <div class="section-label">Billed To:</div>
                <strong style="font-size: 14px;">{{ $invoice->client_name }}</strong><br>
                @if($invoice->client_email)
                    {{ $invoice->client_email }}<br>
                @endif
                @if($invoice->client_address)
                    {!! nl2br(e($invoice->client_address)) !!}
                @endif
            </td>
            <td class="info-col">
                <div class="section-label">Payment Terms:</div>
                Currency: <strong>{{ $invoice->currency }}</strong><br>
                Due within 15 days of issue.<br>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right" style="width: 70px;">Qty</th>
                <th class="text-right" style="width: 100px;">Unit Price</th>
                <th class="text-right" style="width: 100px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 1) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="text-right">Subtotal:</td>
            <td class="text-right"><strong>${{ number_format($invoice->subtotal, 2) }}</strong></td>
        </tr>
        @if($invoice->tax_rate > 0)
            <tr>
                <td class="text-right">Tax ({{ $invoice->tax_rate }}%):</td>
                <td class="text-right">${{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
        @endif
        @if($invoice->discount_amount > 0)
            <tr>
                <td class="text-right">Discount:</td>
                <td class="text-right">-${{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td class="text-right">Total Due:</td>
            <td class="text-right">${{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</td>
        </tr>
    </table>

    @if($invoice->notes)
        <div class="notes-box">
            <strong>Notes & Payment Instructions:</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif

</body>
</html>
