@extends('layouts.app')

@section('title', 'Reports & Exports')
@section('page-title', 'Financial Reports & Data Exports')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Filter Row -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Financial Statements & Exports</h4>
            <p class="text-muted small mb-0">Generate monthly executive summaries, DomPDF printable statements, and Excel spreadsheets.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('reports.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <input type="month" name="month" class="form-control form-control-sm" value="{{ $selectedMonth }}" onchange="this.form.submit()">
            </form>
            <a href="{{ route('reports.pdf', ['month' => $selectedMonth]) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF Statement
            </a>
            <a href="{{ route('reports.excel', ['month' => $selectedMonth]) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel (.xlsx)
            </a>
        </div>
    </div>

    <!-- Monthly Summary Highlights -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Month Revenue / Inflows</div>
                <h3 class="fw-bold text-success mt-2 mb-0">+${{ number_format($income, 2) }}</h3>
                <div class="text-muted small mt-2">{{ \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->format('F Y') }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Month Outflows / Expenses</div>
                <h3 class="fw-bold text-danger mt-2 mb-0">-${{ number_format($expense, 2) }}</h3>
                <div class="text-muted small mt-2">All completed expenditures</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Net Monthly Savings</div>
                <h3 class="fw-bold {{ $savings >= 0 ? 'text-primary' : 'text-danger' }} mt-2 mb-0">
                    {{ $savings >= 0 ? '+' : '' }}${{ number_format($savings, 2) }}
                </h3>
                <div class="text-muted small mt-2">Net Cash Position</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Savings Retention Rate</div>
                <h3 class="fw-bold text-dark mt-2 mb-0">{{ $savingsRate }}%</h3>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: {{ min(100, max(0, $savingsRate)) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Expense Breakdown Table -->
    <div class="card-custom mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-pie-chart text-primary"></i>
                <span class="fw-bold">Expense Breakdown by Category</span>
            </div>
            <span class="badge bg-light text-muted border">{{ \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->format('F Y') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Category</th>
                            <th>Total Amount</th>
                            <th>Share of Outflows</th>
                            <th class="pe-3">Visual Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categorySpending as $cat)
                            @php
                                $share = $expense > 0 ? round(($cat->total / $expense) * 100, 1) : 0;
                            @endphp
                            <tr>
                                <td class="ps-3 fw-semibold text-dark">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 10px; height: 10px; background-color: {{ $cat->color }};"></span>
                                    {{ $cat->name }}
                                </td>
                                <td class="fw-bold text-dark">${{ number_format($cat->total, 2) }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $share }}%</span></td>
                                <td class="pe-3" style="width: 250px;">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" style="width: {{ $share }}%; background-color: {{ $cat->color }};"></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    No categorized expense activity recorded for this month.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Export Actions Card -->
    <div class="card-custom bg-light border-0">
        <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-1">Export Raw Accounting Records</h5>
                <p class="text-muted small mb-0">Download full spreadsheet records compatible with QuickBooks, Xero, Excel, or Google Sheets.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('reports.excel', ['month' => $selectedMonth, 'format' => 'xlsx']) }}" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download .XLSX
                </a>
                <a href="{{ route('reports.excel', ['month' => $selectedMonth, 'format' => 'csv']) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-filetype-csv me-1"></i> Download .CSV
                </a>
                <a href="{{ route('reports.pdf', ['month' => $selectedMonth]) }}" class="btn btn-danger btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Printable PDF
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
