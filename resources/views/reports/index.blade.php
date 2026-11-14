@extends('layouts.app')

@section('title', 'Reports & Analytics')
@section('page-title', 'Financial Reports & Analytics')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Period Switcher Controls -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-bar-graph text-primary"></i> Financial Reports & Analytics
            </h4>
            <p class="text-muted small mb-0">Daily, weekly, and monthly statements, category expense distributions, trend analytics, and savings tracking.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Period Selector Form -->
            <form action="{{ route('reports.index') }}" method="GET" id="reportFilterForm" class="d-flex align-items-center gap-2 m-0">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="period" id="period_daily" value="daily" {{ $period === 'daily' ? 'checked' : '' }} onchange="submitFilter()">
                    <label class="btn btn-outline-primary" for="period_daily">
                        <i class="bi bi-calendar-event me-1"></i>Daily
                    </label>

                    <input type="radio" class="btn-check" name="period" id="period_weekly" value="weekly" {{ $period === 'weekly' ? 'checked' : '' }} onchange="submitFilter()">
                    <label class="btn btn-outline-primary" for="period_weekly">
                        <i class="bi bi-calendar-range me-1"></i>Weekly
                    </label>

                    <input type="radio" class="btn-check" name="period" id="period_monthly" value="monthly" {{ $period === 'monthly' ? 'checked' : '' }} onchange="submitFilter()">
                    <label class="btn btn-outline-primary" for="period_monthly">
                        <i class="bi bi-calendar-month me-1"></i>Monthly
                    </label>
                </div>

                <!-- Dynamic timeframe input -->
                @if($period === 'daily')
                    <input type="date" name="date" class="form-control form-control-sm" style="width: 140px;" value="{{ $selectedDate }}" onchange="submitFilter()">
                @elseif($period === 'weekly')
                    <input type="date" name="week" class="form-control form-control-sm" style="width: 140px;" value="{{ $selectedWeek }}" onchange="submitFilter()">
                @else
                    <input type="month" name="month" class="form-control form-control-sm" style="width: 145px;" value="{{ $selectedMonth }}" onchange="submitFilter()">
                @endif
            </form>

            <!-- Export Actions Dropdown -->
            <div class="btn-group">
                <a href="{{ route('reports.pdf', ['period' => $period, 'date' => $selectedDate, 'week' => $selectedWeek, 'month' => $selectedMonth]) }}" class="btn btn-outline-danger btn-sm shadow-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                </a>
                <a href="{{ route('reports.excel', ['period' => $period, 'date' => $selectedDate, 'week' => $selectedWeek, 'month' => $selectedMonth, 'format' => 'xlsx']) }}" class="btn btn-outline-success btn-sm shadow-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel (.xlsx)
                </a>
                <a href="{{ route('reports.excel', ['period' => $period, 'date' => $selectedDate, 'week' => $selectedWeek, 'month' => $selectedMonth, 'format' => 'csv']) }}" class="btn btn-outline-secondary btn-sm shadow-sm" title="Export CSV">
                    <i class="bi bi-filetype-csv me-1"></i> CSV
                </a>
            </div>
        </div>
    </div>

    <!-- Period Title Banner -->
    <div class="card border-0 shadow-sm mb-4 bg-primary bg-opacity-10 border-start border-primary border-4">
        <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi {{ $period === 'daily' ? 'bi-calendar-event' : ($period === 'weekly' ? 'bi-calendar-range' : 'bi-calendar-month') }} fs-5"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0">{{ $label }}: {{ $formatted_date }}</h6>
                    <span class="text-muted small">Date Span: {{ $start_date }} to {{ $end_date }} ({{ $days_in_range }} day{{ $days_in_range > 1 ? 's' : '' }})</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 text-muted small">
                <span><strong>{{ $transactions_count }}</strong> Transactions</span> &bull;
                <span>Avg Daily Burn: <strong>${{ number_format($daily_avg_spend, 2) }}/d</strong></span>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- Revenue / Inflow -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Total Inflows</div>
                    <i class="bi bi-arrow-down-left-circle text-success fs-5"></i>
                </div>
                <h3 class="fw-bold text-success mt-2 mb-0">+${{ number_format($income, 2) }}</h3>
                <div class="text-muted small mt-2">Recognized Revenue</div>
            </div>
        </div>

        <!-- Outflows / Expenses -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Total Outflows</div>
                    <i class="bi bi-arrow-up-right-circle text-danger fs-5"></i>
                </div>
                <h3 class="fw-bold text-danger mt-2 mb-0">-${{ number_format($expense, 2) }}</h3>
                <div class="text-muted small mt-2">Completed Expenditures</div>
            </div>
        </div>

        <!-- Net Savings -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Net Savings</div>
                    <span class="badge {{ $savings >= 0 ? 'bg-primary' : 'bg-danger' }}">
                        {{ $savings >= 0 ? 'Surplus' : 'Deficit' }}
                    </span>
                </div>
                <h3 class="fw-bold {{ $savings >= 0 ? 'text-primary' : 'text-danger' }} mt-2 mb-0">
                    {{ $savings >= 0 ? '+' : '' }}${{ number_format($savings, 2) }}
                </h3>
                <div class="text-muted small mt-2">Net Cash Generation</div>
            </div>
        </div>

        <!-- Savings Rate -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Savings Retention</div>
                    <i class="bi bi-piggy-bank text-info fs-5"></i>
                </div>
                <h3 class="fw-bold {{ $savings_rate >= 25 ? 'text-success' : ($savings_rate >= 10 ? 'text-warning' : 'text-danger') }} mt-2 mb-0">
                    {{ $savings_rate }}%
                </h3>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar {{ $savings_rate >= 25 ? 'bg-success' : ($savings_rate >= 10 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ min(100, max(0, $savings_rate)) }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for Reports -->
    <ul class="nav nav-pills mb-4 bg-light p-1 rounded border" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overviewTabPane" type="button" role="tab">
                <i class="bi bi-graph-up-arrow me-1"></i> Income & Expense Trends
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'category_breakdown' ? 'active' : '' }}" id="category-tab" data-bs-toggle="tab" data-bs-target="#categoryTabPane" type="button" role="tab">
                <i class="bi bi-pie-chart-fill me-1"></i> Category-Wise Expenses
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'savings' ? 'active' : '' }}" id="savings-tab" data-bs-toggle="tab" data-bs-target="#savingsTabPane" type="button" role="tab">
                <i class="bi bi-piggy-bank-fill me-1"></i> Savings Analysis
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'transactions' ? 'active' : '' }}" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactionsTabPane" type="button" role="tab">
                <i class="bi bi-list-check me-1"></i> Transaction Register ({{ $transactions_count }})
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="reportTabContent">

        <!-- TAB 1: INCOME & EXPENSE TRENDS -->
        <div class="tab-pane fade {{ $activeTab === 'overview' ? 'show active' : '' }}" id="overviewTabPane" role="tabpanel">
            <div class="card-custom mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Income vs. Expense Trend Trajectory</h6>
                        <span class="text-muted small">Comparative timeline of revenue inflows vs. expense outflows for {{ $formatted_date }}.</span>
                    </div>
                    <span class="badge bg-light text-secondary border">
                        {{ $period === 'monthly' ? '6-Month Trajectory' : ($period === 'weekly' ? 'Weekly Daily Breakdown' : '7-Day Rolling Trend') }}
                    </span>
                </div>
                <div class="card-body">
                    <div style="height: 320px; width: 100%;">
                        <canvas id="incomeExpenseTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Trend Insights Cards -->
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card-custom h-100 p-3">
                        <div class="text-muted small fw-semibold text-uppercase">Average Spending Velocity</div>
                        <h4 class="fw-bold text-dark mt-2 mb-0">${{ number_format($daily_avg_spend, 2) }} <span class="fs-6 text-muted fw-normal">/ day</span></h4>
                        <p class="text-muted small mt-2 mb-0">Calculated over {{ $days_in_range }} day(s) of this reporting cycle.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom h-100 p-3">
                        <div class="text-muted small fw-semibold text-uppercase">Largest Single Expenditure</div>
                        @if($largest_expense)
                            <h4 class="fw-bold text-danger mt-2 mb-0">${{ number_format($largest_expense->amount, 2) }}</h4>
                            <p class="text-muted small mt-2 mb-0 text-truncate">
                                <strong>{{ $largest_expense->description }}</strong> ({{ $largest_expense->category->name ?? 'General' }})
                            </p>
                        @else
                            <h4 class="fw-bold text-muted mt-2 mb-0">$0.00</h4>
                            <p class="text-muted small mt-2 mb-0">No expenses recorded.</p>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-custom h-100 p-3">
                        <div class="text-muted small fw-semibold text-uppercase">Net Operating Margin</div>
                        <h4 class="fw-bold {{ $savings >= 0 ? 'text-success' : 'text-danger' }} mt-2 mb-0">
                            {{ $income > 0 ? round(($savings / $income) * 100, 1) : 0 }}%
                        </h4>
                        <p class="text-muted small mt-2 mb-0">Percentage of top-line revenue converted to net liquidity.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: CATEGORY-WISE EXPENSES -->
        <div class="tab-pane fade {{ $activeTab === 'category_breakdown' ? 'show active' : '' }}" id="categoryTabPane" role="tabpanel">
            <div class="row g-4 mb-4">
                <!-- Doughnut Chart -->
                <div class="col-lg-5">
                    <div class="card-custom h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="fw-bold mb-0 text-dark">Category Outflow Distribution</h6>
                            <span class="text-muted small">Relative proportion of expenses across categories.</span>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 280px;">
                            @if(count($category_breakdown) > 0)
                                <div style="height: 250px; width: 100%;">
                                    <canvas id="categoryDoughnutChart"></canvas>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">No expense distributions recorded.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown Table -->
                <div class="col-lg-7">
                    <div class="card-custom h-100">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="fw-bold mb-0 text-dark">Category Expenditure Matrix</h6>
                            <span class="text-muted small">Aggregated costs including subcategory rollups.</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-end">Share</th>
                                        <th class="text-end">Avg / Tx</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($category_breakdown as $c)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded p-1 text-white" style="background-color: {{ $c['color'] }}; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="bi {{ $c['icon'] }}" style="font-size: 0.85rem;"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold text-dark">{{ $c['name'] }}</div>
                                                        <span class="text-muted" style="font-size: 0.72rem;">{{ $c['transaction_count'] }} tx{{ $c['transaction_count'] > 1 ? 's' : '' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end fw-bold text-dark">${{ number_format($c['total_spent'], 2) }}</td>
                                            <td class="text-end">
                                                <span class="badge bg-light text-secondary border">{{ $c['share_percentage'] }}%</span>
                                            </td>
                                            <td class="text-end text-muted small">${{ number_format($c['average_transaction'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No categorized expenditures found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: SAVINGS ANALYSIS -->
        <div class="tab-pane fade {{ $activeTab === 'savings' ? 'show active' : '' }}" id="savingsTabPane" role="tabpanel">
            <div class="card-custom mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-dark">Savings Performance & Wealth Accumulation Analysis</h6>
                    <span class="text-muted small">Comprehensive evaluation of liquidity retention, benchmark compliance, and projected annual reserves.</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <h6 class="fw-bold text-dark mb-2">Savings Retention Rating</h6>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge {{ $savings_analysis['status'] === 'excellent' ? 'bg-success' : ($savings_analysis['status'] === 'moderate' ? 'bg-primary' : ($savings_analysis['status'] === 'warning' ? 'bg-warning text-dark' : 'bg-danger')) }} px-3 py-2 fs-6">
                                        {{ $savings_analysis['status_label'] }}
                                    </span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Your current savings retention is <strong>{{ $savings_rate }}%</strong> compared to the recommended 25% small business reserve benchmark.
                                </p>
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Current: {{ $savings_rate }}%</span>
                                    <span>Target: {{ $savings_analysis['benchmark_target'] }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ min(100, max(0, $savings_rate)) }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border h-100">
                                <h6 class="fw-bold text-dark mb-2">Annual Savings Projections</h6>
                                <div class="d-flex align-items-baseline gap-2 mb-1">
                                    <h3 class="fw-bold text-primary mb-0">${{ number_format($savings_analysis['projected_annual_savings'], 2) }}</h3>
                                    <span class="text-muted small">/ projected year</span>
                                </div>
                                <p class="text-muted small mb-3">
                                    Extrapolating your net cash generation of ${{ number_format($savings, 2) }} for this {{ $period }} cycle over a full 12-month period.
                                </p>
                                <div class="p-2 bg-white rounded border small text-secondary">
                                    <i class="bi bi-info-circle text-primary me-1"></i>
                                    <strong>All-Time Cumulative Savings:</strong> ${{ number_format($savings_analysis['cumulative_savings'], 2) }} ({{ $savings_analysis['cumulative_savings_rate'] }}% cumulative retention).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: TRANSACTION REGISTER -->
        <div class="tab-pane fade {{ $activeTab === 'transactions' ? 'show active' : '' }}" id="transactionsTabPane" role="tabpanel">
            <div class="card-custom">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Transaction Register for {{ $formatted_date }}</h6>
                        <span class="text-muted small">Complete journal of accounting line items within this reporting window.</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('reports.excel', ['period' => $period, 'date' => $selectedDate, 'week' => $selectedWeek, 'month' => $selectedMonth, 'format' => 'xlsx']) }}" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('reports.excel', ['period' => $period, 'date' => $selectedDate, 'week' => $selectedWeek, 'month' => $selectedMonth, 'format' => 'csv']) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-filetype-csv me-1"></i> Export CSV
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Account</th>
                                <th>Payment</th>
                                <th class="text-end pe-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tx)
                                <tr>
                                    <td class="ps-3 text-muted small">{{ $tx->transaction_date->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge {{ $tx->type === 'income' ? 'bg-success' : ($tx->type === 'expense' ? 'bg-danger' : 'bg-info') }}">
                                            {{ ucfirst($tx->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $tx->category->name ?? 'General' }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $tx->description }}</div>
                                        @if($tx->payee_payer)
                                            <span class="text-muted small" style="font-size: 0.72rem;">{{ $tx->payee_payer }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $tx->account->name ?? '-' }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $tx->payment_method)) }}</td>
                                    <td class="text-end pe-3 fw-bold {{ $tx->type === 'income' ? 'text-success' : ($tx->type === 'expense' ? 'text-danger' : 'text-primary') }}">
                                        {{ $tx->type === 'income' ? '+' : ($tx->type === 'expense' ? '-' : '') }}${{ number_format($tx->amount, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No transactions found for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
function submitFilter() {
    document.getElementById('reportFilterForm').submit();
}

document.addEventListener('DOMContentLoaded', function () {
    // 1. Income vs Expense Trend Chart
    const trendCtx = document.getElementById('incomeExpenseTrendChart');
    if (trendCtx) {
        const trendData = @json($trend_data);

        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trendData.labels,
                datasets: [
                    {
                        label: 'Inflows / Revenue ($)',
                        data: trendData.income,
                        backgroundColor: 'rgba(16, 185, 129, 0.75)',
                        borderColor: '#059669',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Outflows / Expenses ($)',
                        data: trendData.expense,
                        backgroundColor: 'rgba(239, 68, 68, 0.75)',
                        borderColor: '#dc2626',
                        borderWidth: 1,
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) {
                                return '$' + val.toLocaleString();
                            }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Category Outflow Doughnut Chart
    const catCtx = document.getElementById('categoryDoughnutChart');
    if (catCtx) {
        const categories = @json($category_breakdown);
        if (categories.length > 0) {
            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: categories.map(c => c.name),
                    datasets: [{
                        data: categories.map(c => c.total_spent),
                        backgroundColor: categories.map(c => c.color || '#3b82f6'),
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12 } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': $' + context.parsed.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>
@endpush
@endsection
