@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Financial Dashboard')

@section('content')
<div class="container-fluid px-0">

    <!-- AI Alert / Highlight Banner -->
    @if($latestInsight)
        <div class="ai-card p-3 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; min-width: 42px;">
                    <i class="bi bi-stars fs-5"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $latestInsight->priority === 'high' ? 'bg-danger' : ($latestInsight->priority === 'medium' ? 'bg-warning text-dark' : 'bg-info text-dark') }} text-uppercase" style="font-size: 0.65rem;">
                            {{ $latestInsight->priority }} Priority
                        </span>
                        <h6 class="mb-0 fw-bold text-dark">{{ $latestInsight->title }}</h6>
                    </div>
                    <p class="mb-0 small text-secondary mt-1">{{ $latestInsight->summary }}</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <a href="{{ route('ai.index') }}" class="btn btn-sm btn-primary text-nowrap w-100 w-md-auto text-center">
                    Review Insight <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    @endif

    <!-- Row 1: KPI Stat Cards -->
    <div class="row g-3 mb-4">
        <!-- Current Balance -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase tracking-wider">Current Balance</div>
                        <h3 class="fw-bold text-dark mt-2 mb-0">
                            {{ $currencySymbol ?? '₹' }}{{ number_format($totalBalance, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-light d-flex align-items-center justify-content-between text-muted small">
                    <span><i class="bi bi-bank me-1 text-primary"></i> {{ $accounts->count() }} Active Accounts</span>
                    <a href="{{ route('accounts.index') }}" class="text-decoration-none small text-primary fw-semibold">Manage <i class="bi bi-chevron-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Monthly Income -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase tracking-wider">Monthly Income</div>
                        <h3 class="fw-bold text-success mt-2 mb-0">
                            +{{ $currencySymbol ?? '₹' }}{{ number_format($monthlyIncome, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-arrow-down-left-circle"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-light d-flex align-items-center justify-content-between text-muted small">
                    <span>{{ now()->format('F Y') }}</span>
                    <span class="text-muted">All-time: {{ $currencySymbol ?? '₹' }}{{ number_format($allTimeIncome, 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Monthly Expenses -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase tracking-wider">Monthly Expenses</div>
                        <h3 class="fw-bold text-danger mt-2 mb-0">
                            -{{ $currencySymbol ?? '₹' }}{{ number_format($monthlyExpenses, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-arrow-up-right-circle"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-light d-flex align-items-center justify-content-between text-muted small">
                    <span>{{ now()->format('F Y') }}</span>
                    <span class="text-muted">All-time: {{ $currencySymbol ?? '₹' }}{{ number_format($allTimeExpenses, 0) }}</span>
                </div>
            </div>
        </div>

        <!-- Monthly Savings & Savings Rate -->
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100 d-flex flex-column justify-content-between">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase tracking-wider">Monthly Savings</div>
                        <h3 class="fw-bold {{ $monthlySavings >= 0 ? 'text-primary' : 'text-danger' }} mt-2 mb-0">
                            {{ $monthlySavings >= 0 ? '+' : '-' }}{{ $currencySymbol ?? '₹' }}{{ number_format(abs($monthlySavings), 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-piggy-bank"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top border-light d-flex align-items-center justify-content-between text-muted small">
                    <span>Savings Rate:</span>
                    <span class="badge {{ $savingsRate >= 20 ? 'bg-success' : ($savingsRate > 0 ? 'bg-warning text-dark' : 'bg-danger') }} px-2 py-1">
                        {{ $savingsRate }}%
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Charts Row (Chart.js) -->
    <div class="row g-3 mb-4">
        <!-- 6-Month Income vs Expense Trend -->
        <div class="col-lg-8">
            <div class="card-custom h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bar-chart-line-fill text-primary"></i>
                        <span class="fw-bold">Cashflow Trend (Income vs Expenses)</span>
                    </div>
                    <span class="badge bg-light text-muted border">Past 6 Months</span>
                </div>
                <div class="card-body">
                    <div style="height: 310px; position: relative;">
                        <canvas id="cashflowTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Spending Doughnut Chart -->
        <div class="col-lg-4">
            <div class="card-custom h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-pie-chart-fill text-info"></i>
                        <span class="fw-bold">Spend by Category</span>
                    </div>
                    <span class="badge bg-light text-muted border">This Month</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    @if(count($categoryTotals) > 0)
                        <div style="height: 230px; width: 100%; position: relative;">
                            <canvas id="categoryExpenseChart"></canvas>
                        </div>
                        <div class="mt-3 text-center small text-muted">
                            Total Tracked: <strong class="text-dark">${{ number_format($monthlyExpenses, 2) }}</strong>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                            No expense transactions recorded this month yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Recent Transactions & Budget Utilization Grid -->
    <div class="row g-3">
        <!-- Recent Transactions -->
        <div class="col-lg-8">
            <div class="card-custom h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-secondary"></i>
                        <span class="fw-bold">Recent Transactions</span>
                    </div>
                    <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Account</th>
                                    <th class="text-end pe-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $tx)
                                    <tr>
                                        <td class="ps-3 text-nowrap text-secondary small">
                                            {{ $tx->transaction_date ? $tx->transaction_date->format('M d, Y') : '-' }}
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;">
                                                {{ $tx->description }}
                                            </div>
                                            @if($tx->payee_payer)
                                                <div class="text-muted small">{{ $tx->payee_payer }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($tx->category)
                                                <span class="badge rounded-pill bg-light text-dark border px-2 py-1">
                                                    <i class="bi {{ $tx->category->icon ?? 'bi-tag' }} me-1" style="color: {{ $tx->category->color }}"></i>
                                                    {{ $tx->category->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted">Transfer</span>
                                            @endif
                                        </td>
                                        <td class="small text-muted text-nowrap">
                                            <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: {{ $tx->account->color ?? '#2563eb' }};"></span>
                                            {{ $tx->account->name ?? 'Default' }}
                                        </td>
                                        <td class="text-end pe-3 text-nowrap">
                                            @if($tx->type === 'income')
                                                <span class="amount-income">+${{ number_format($tx->amount, 2) }}</span>
                                            @elseif($tx->type === 'expense')
                                                <span class="amount-expense">-${{ number_format($tx->amount, 2) }}</span>
                                            @else
                                                <span class="text-primary fw-semibold">${{ number_format($tx->amount, 2) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No transactions found. Click "Record Transaction" to get started!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Utilization & Financial Overview -->
        <div class="col-lg-4">
            <div class="d-flex flex-column gap-3">
                <!-- Budget Utilization Card -->
                <div class="card-custom">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-speedometer2 text-warning"></i>
                            <span class="fw-bold">Budget Utilization</span>
                        </div>
                        <a href="{{ route('budgets.index') }}" class="small text-decoration-none text-primary fw-semibold">
                            Manage
                        </a>
                    </div>
                    <div class="card-body">
                        @forelse($budgets as $b)
                            @php
                                $pct = min(100, $b->utilization_percentage);
                                $progressClass = $b->status === 'exceeded' ? 'bg-danger' : ($b->status === 'warning' ? 'bg-warning' : 'bg-primary');
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="fw-semibold text-dark">{{ $b->name }}</span>
                                    <span class="text-muted">
                                        ${{ number_format($b->spent, 0) }} / ${{ number_format($b->amount, 0) }}
                                        <span class="fw-bold {{ $b->status === 'exceeded' ? 'text-danger' : 'text-dark' }}">({{ $b->utilization_percentage }}%)</span>
                                    </span>
                                </div>
                                <div class="progress" style="height: 7px;">
                                    <div class="progress-bar {{ $progressClass }}" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-3 text-muted small">
                                No budgets active for this month.
                                <div class="mt-2">
                                    <a href="{{ route('budgets.index') }}" class="btn btn-sm btn-outline-primary">Create a Budget</a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Financial Overview: Accounts Summary -->
                <div class="card-custom">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-success"></i>
                            <span class="fw-bold">Liquid Reserves</span>
                        </div>
                        <span class="badge bg-light text-dark border">{{ $user->currency }}</span>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($accounts as $acc)
                                <li class="list-group-item d-flex align-items-center justify-content-between py-2 px-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle" style="width: 10px; height: 10px; background-color: {{ $acc->color }};"></div>
                                        <div>
                                            <div class="small fw-semibold text-dark">{{ $acc->name }}</div>
                                            <div class="text-muted" style="font-size: 0.725rem;">
                                                {{ ucwords(str_replace('_', ' ', $acc->type)) }}
                                                @if($acc->account_number_last4) •••• {{ $acc->account_number_last4 }} @endif
                                            </div>
                                        </div>
                                    </div>
                                    <span class="fw-bold small {{ $acc->balance < 0 ? 'text-danger' : 'text-dark' }}">
                                        ${{ number_format($acc->balance, 2) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Render Cashflow Trend Chart (Bar & Line Combo)
    const trendCtx = document.getElementById('cashflowTrendChart').getContext('2d');
    const trendLabels = @json($trendMonths);
    const incomeData = @json($trendIncome);
    const expenseData = @json($trendExpense);

    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: trendLabels,
            datasets: [
                {
                    label: 'Income',
                    data: incomeData,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Expenses',
                    data: expenseData,
                    backgroundColor: 'rgba(239, 68, 68, 0.85)',
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { boxWidth: 12, font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': $' + Number(context.raw).toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return '$' + Number(value).toLocaleString(); }
                    },
                    grid: { color: 'rgba(226, 232, 240, 0.6)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // 2. Render Spend by Category Doughnut Chart
    const catCanvas = document.getElementById('categoryExpenseChart');
    if (catCanvas) {
        const catCtx = catCanvas.getContext('2d');
        const catLabels = @json($categoryLabels);
        const catTotals = @json($categoryTotals);
        const catColors = @json($categoryColors);

        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catTotals,
                    backgroundColor: catColors.length ? catColors : ['#3b82f6', '#ef4444', '#f59e0b', '#10b981', '#6366f1', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 11 } }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': $' + Number(context.raw).toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush

