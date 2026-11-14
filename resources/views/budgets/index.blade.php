@extends('layouts.app')

@section('title', 'Budget Management')
@section('page-title', 'Budget & Spending Limit Management')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Period Filter Controls -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">Budget & Spending Limits</h4>
            <p class="text-muted small mb-0">Track monthly & yearly spending limits, monitor category-wise allocations, and resolve budget alerts.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Period & Timeframe Switcher Form -->
            <form action="{{ route('budgets.index') }}" method="GET" id="filterForm" class="d-flex align-items-center gap-2 m-0">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="period" id="period_monthly" value="monthly" {{ $period === 'monthly' ? 'checked' : '' }} onchange="document.getElementById('filterForm').submit()">
                    <label class="btn btn-outline-primary" for="period_monthly">
                        <i class="bi bi-calendar-month me-1"></i>Monthly
                    </label>

                    <input type="radio" class="btn-check" name="period" id="period_yearly" value="yearly" {{ $period === 'yearly' ? 'checked' : '' }} onchange="document.getElementById('filterForm').submit()">
                    <label class="btn btn-outline-primary" for="period_yearly">
                        <i class="bi bi-calendar3 me-1"></i>Yearly
                    </label>
                </div>

                @if($period === 'yearly')
                    <select name="year" class="form-select form-select-sm" style="width: 110px;" onchange="document.getElementById('filterForm').submit()">
                        @foreach($availableYears as $yr)
                            <option value="{{ $yr }}" {{ (string)$selectedYear === (string)$yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="month" name="month" class="form-control form-control-sm" style="width: 145px;" value="{{ $selectedMonth }}" onchange="document.getElementById('filterForm').submit()">
                @endif
            </form>

            <!-- Export Comparison CSV -->
            <a href="{{ route('budgets.export', ['period' => $period, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm" title="Export Budget vs Actual to CSV">
                <i class="bi bi-file-earmark-spreadsheet"></i>
                <span class="d-none d-sm-inline">Export CSV</span>
            </a>

            <!-- Create Budget Modal Trigger -->
            <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#newBudgetModal">
                <i class="bi bi-plus-lg"></i>
                <span>New Budget</span>
            </button>
        </div>
    </div>

    <!-- Active Budget Alerts Banner -->
    @if(count($activeAlerts) > 0)
        <div class="card border-0 shadow-sm mb-4 bg-danger bg-opacity-10 border-start border-danger border-4">
            <div class="card-body p-3">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-danger text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-bell-fill fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-danger mb-0">
                                {{ count($activeAlerts) }} Active Budget Alert{{ count($activeAlerts) > 1 ? 's' : '' }}
                            </h6>
                            <div class="text-muted small">
                                Spending limits or warning thresholds have been reached for {{ $period }} period.
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#alertListCollapse">
                        <i class="bi bi-eye me-1"></i>Toggle Details
                    </button>
                </div>

                <div class="collapse show mt-3" id="alertListCollapse">
                    <div class="row g-2">
                        @foreach($activeAlerts as $alert)
                            <div class="col-md-6">
                                <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between shadow-xs">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <span class="badge {{ $alert['severity'] === 'danger' ? 'bg-danger' : 'bg-warning text-dark' }} px-2 py-1">
                                            {{ $alert['severity'] === 'danger' ? 'LIMIT BREACHED' : 'CAUTION ' . $alert['percent'] . '%' }}
                                        </span>
                                        <div class="text-truncate">
                                            <div class="fw-bold text-dark small text-truncate">{{ $alert['category_name'] }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">${{ number_format($alert['spent'], 2) }} of ${{ number_format($alert['limit'], 2) }} limit</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-0 ps-2" onclick="editBudget({{ $alert['budget']->id }})">
                                        Adjust
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- Total Budgeted Target -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Budget Target</div>
                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ ucfirst($period) }}</span>
                </div>
                <h3 class="fw-bold text-dark mt-2 mb-0">${{ number_format($summary['total_budgeted'], 2) }}</h3>
                <div class="text-muted small mt-2">
                    <i class="bi bi-wallet2 me-1"></i>{{ $summary['total_count'] }} Active Budgets
                </div>
            </div>
        </div>

        <!-- Total Spending Limit -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Spending Ceiling</div>
                    <i class="bi bi-shield-lock text-secondary"></i>
                </div>
                <h3 class="fw-bold text-secondary mt-2 mb-0">${{ number_format($summary['total_spending_limit'], 2) }}</h3>
                <div class="text-muted small mt-2">
                    Hard Cap: ${{ number_format(max(0, $summary['total_spending_limit'] - $summary['total_budgeted']), 2) }} buffer
                </div>
            </div>
        </div>

        <!-- Total Spent -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Total Spent</div>
                    <span class="badge {{ $summary['overall_percentage'] > 100 ? 'bg-danger' : ($summary['overall_percentage'] >= 80 ? 'bg-warning text-dark' : 'bg-success') }}">
                        {{ $summary['overall_percentage'] }}%
                    </span>
                </div>
                <h3 class="fw-bold {{ $summary['overall_percentage'] > 100 ? 'text-danger' : 'text-dark' }} mt-2 mb-0">
                    ${{ number_format($summary['total_spent'], 2) }}
                </h3>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar {{ $summary['overall_percentage'] > 100 ? 'bg-danger' : ($summary['overall_percentage'] >= 80 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ min(100, $summary['overall_percentage']) }}%"></div>
                </div>
            </div>
        </div>

        <!-- Remaining Safe Pool & Daily Allowance -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Remaining Pool</div>
                    <i class="bi bi-speedometer2 text-info"></i>
                </div>
                <h3 class="fw-bold {{ $summary['total_remaining'] >= 0 ? 'text-success' : 'text-danger' }} mt-2 mb-0">
                    ${{ number_format($summary['total_remaining'], 2) }}
                </h3>
                <div class="text-muted small mt-2">
                    Daily Allowance: <strong class="text-dark">${{ number_format($summary['daily_allowance'], 2) }}</strong>/day
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for Budget Management -->
    <ul class="nav nav-pills mb-4 bg-light p-1 rounded border" id="budgetTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overviewTabPane" type="button" role="tab">
                <i class="bi bi-grid-fill me-1"></i> Budget Cards & Limits
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'category_wise' ? 'active' : '' }}" id="category-tab" data-bs-toggle="tab" data-bs-target="#categoryTabPane" type="button" role="tab">
                <i class="bi bi-tags-fill me-1"></i> Category-Wise Budgets
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'vs_actual' ? 'active' : '' }}" id="vsactual-tab" data-bs-toggle="tab" data-bs-target="#vsActualTabPane" type="button" role="tab">
                <i class="bi bi-bar-chart-line-fill me-1"></i> Budget vs Actual Comparison
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'alerts' ? 'active' : '' }}" id="alerts-tab" data-bs-toggle="tab" data-bs-target="#alertsTabPane" type="button" role="tab">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> Alerts & Thresholds
                @if(count($activeAlerts) > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ count($activeAlerts) }}</span>
                @endif
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="budgetTabContent">

        <!-- TAB 1: OVERVIEW / BUDGET CARDS -->
        <div class="tab-pane fade {{ $activeTab === 'overview' ? 'show active' : '' }}" id="overviewTabPane" role="tabpanel">

            <!-- Status Filter Bar -->
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-1">
                    <span class="text-muted small me-2">Filter:</span>
                    <a href="{{ route('budgets.index', ['period' => $period, 'month' => $selectedMonth, 'year' => $selectedYear, 'tab' => 'overview', 'status' => 'all']) }}" class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-secondary text-white' : 'btn-outline-secondary' }}">
                        All ({{ $summary['total_count'] }})
                    </a>
                    <a href="{{ route('budgets.index', ['period' => $period, 'month' => $selectedMonth, 'year' => $selectedYear, 'tab' => 'overview', 'status' => 'exceeded']) }}" class="btn btn-sm {{ $statusFilter === 'exceeded' ? 'btn-danger text-white' : 'btn-outline-danger' }}">
                        Exceeded ({{ $summary['exceeded_count'] }})
                    </a>
                    <a href="{{ route('budgets.index', ['period' => $period, 'month' => $selectedMonth, 'year' => $selectedYear, 'tab' => 'overview', 'status' => 'warning']) }}" class="btn btn-sm {{ $statusFilter === 'warning' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
                        Warning ({{ $summary['warning_count'] }})
                    </a>
                    <a href="{{ route('budgets.index', ['period' => $period, 'month' => $selectedMonth, 'year' => $selectedYear, 'tab' => 'overview', 'status' => 'normal']) }}" class="btn btn-sm {{ $statusFilter === 'normal' ? 'btn-success text-white' : 'btn-outline-success' }}">
                        Healthy ({{ $summary['normal_count'] }})
                    </a>
                </div>
            </div>

            <!-- Budgets Grid -->
            <div class="row g-3">
                @forelse($budgets as $b)
                    @php
                        $pct = min(100, $b->utilization_percentage);
                        $isExceeded = $b->status === 'exceeded';
                        $isWarning = $b->status === 'warning';
                        $badgeClass = $isExceeded ? 'bg-danger' : ($isWarning ? 'bg-warning text-dark' : 'bg-success');
                        $barClass = $isExceeded ? 'bg-danger' : ($isWarning ? 'bg-warning' : 'bg-primary');
                    @endphp
                    <div class="col-lg-4 col-md-6">
                        <div class="card-custom h-100 shadow-sm border {{ $isExceeded ? 'border-danger' : ($isWarning ? 'border-warning' : 'border-light') }}">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-light rounded p-2 text-primary">
                                            <i class="bi {{ $b->category->icon ?? 'bi-pie-chart' }} fs-5" style="color: {{ $b->category->color ?? '#2563eb' }}"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark">{{ $b->name }}</h6>
                                            <div class="text-muted small">
                                                <span>{{ $b->category->name ?? 'General Spending' }}</span>
                                                <span class="badge bg-light text-secondary border ms-1">{{ ucfirst($b->period) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge {{ $badgeClass }} px-2 py-1 text-uppercase" style="font-size: 0.65rem;">
                                        {{ $b->status }}
                                    </span>
                                </div>

                                <!-- Spent vs Limit Display -->
                                <div class="d-flex align-items-baseline justify-content-between mb-2">
                                    <div>
                                        <span class="fs-4 fw-bold text-dark">${{ number_format($b->spent, 2) }}</span>
                                        <span class="text-muted small"> / ${{ number_format($b->effective_limit, 2) }} limit</span>
                                    </div>
                                    <span class="fw-bold {{ $isExceeded ? 'text-danger' : ($isWarning ? 'text-warning' : 'text-muted') }} small">
                                        {{ $b->utilization_percentage }}%
                                    </span>
                                </div>

                                <!-- Progress Bar with Alert Threshold Line -->
                                <div class="position-relative mb-2">
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar {{ $barClass }}" role="progressbar" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="position-absolute top-0 bottom-0" style="left: {{ $b->alert_threshold }}%; width: 2px; background: rgba(0,0,0,0.3);" title="Threshold {{ $b->alert_threshold }}%"></div>
                                </div>

                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                                    <span>Target: ${{ number_format($b->amount, 2) }}</span>
                                    <span>Threshold: {{ $b->alert_threshold }}%</span>
                                </div>

                                @if($b->notes)
                                    <div class="p-2 bg-light rounded mt-2 mb-2 small text-secondary fst-italic">
                                        "{{ $b->notes }}"
                                    </div>
                                @endif

                                <!-- Metrics Breakdown -->
                                <div class="mt-auto pt-3 border-top mt-3">
                                    <div class="row g-2 text-center mb-2">
                                        <div class="col-6 border-end">
                                            <div class="text-muted small" style="font-size: 0.7rem;">REMAINING</div>
                                            <div class="fw-bold {{ $b->remaining < 0 ? 'text-danger' : 'text-success' }}">
                                                ${{ number_format($b->remaining, 2) }}
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-muted small" style="font-size: 0.7rem;">DAILY ALLOWANCE</div>
                                            <div class="fw-bold text-dark">
                                                ${{ number_format($b->daily_budget, 2) }}/d
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions: Edit & Delete -->
                                    <div class="d-flex align-items-center justify-content-between pt-2">
                                        <span class="text-muted small" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock me-1"></i>{{ $b->days_remaining }} days left
                                        </span>
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="editBudget({{ $b->id }})" title="Edit Budget">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form action="{{ route('budgets.destroy', $b) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this budget?');" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Delete Budget">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card-custom text-center py-5">
                            <i class="bi bi-calculator fs-1 text-secondary mb-2"></i>
                            <h5>No Budgets Found for {{ ucfirst($period) }} View</h5>
                            <p class="text-muted small mb-3">Create spending limits and category budgets to monitor financial targets proactively.</p>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newBudgetModal">
                                <i class="bi bi-plus-lg me-1"></i> Create First Budget
                            </button>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- TAB 2: CATEGORY-WISE BUDGETS -->
        <div class="tab-pane fade {{ $activeTab === 'category_wise' ? 'show active' : '' }}" id="categoryTabPane" role="tabpanel">
            <div class="card-custom">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Category-Wise Budget Matrix</h6>
                        <span class="text-muted small">Overview of all expense categories, assigned spending limits, and actual burn.</span>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newBudgetModal">
                        <i class="bi bi-plus-lg me-1"></i> Set New Category Budget
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Target Budget</th>
                                <th>Spending Limit</th>
                                <th>Actual Spent</th>
                                <th style="min-width: 140px;">Utilization</th>
                                <th>Remaining / Variance</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryComparison as $catItem)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded p-2 text-white" style="background-color: {{ $catItem['color'] }}; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi {{ $catItem['icon'] }}"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $catItem['category_name'] }}</div>
                                                @if($catItem['subcategories_count'] > 0)
                                                    <span class="text-muted small" style="font-size: 0.72rem;">{{ $catItem['subcategories_count'] }} subcategories rolling up</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($catItem['has_budget'])
                                            <span class="fw-semibold text-dark">${{ number_format($catItem['budget_amount'], 2) }}</span>
                                        @else
                                            <span class="text-muted small fst-italic">None set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($catItem['has_budget'])
                                            <span class="fw-semibold text-secondary">${{ number_format($catItem['spending_limit'], 2) }}</span>
                                        @else
                                            <span class="text-muted small fst-italic">No limit</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold {{ $catItem['spent'] > 0 ? 'text-dark' : 'text-muted' }}">${{ number_format($catItem['spent'], 2) }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar {{ $catItem['status'] === 'exceeded' ? 'bg-danger' : ($catItem['status'] === 'warning' ? 'bg-warning' : 'bg-primary') }}" style="width: {{ min(100, $catItem['utilization_percentage']) }}%"></div>
                                            </div>
                                            <span class="small fw-semibold text-muted" style="width: 45px;">{{ $catItem['utilization_percentage'] }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($catItem['has_budget'])
                                            <span class="fw-bold {{ $catItem['variance_amount'] < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $catItem['variance_amount'] < 0 ? '-' : '+' }}${{ number_format(abs($catItem['variance_amount']), 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-${{ number_format($catItem['spent'], 2) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($catItem['status'] === 'exceeded')
                                            <span class="badge bg-danger">EXCEEDED</span>
                                        @elseif($catItem['status'] === 'warning')
                                            <span class="badge bg-warning text-dark">WARNING</span>
                                        @elseif($catItem['status'] === 'normal')
                                            <span class="badge bg-success">ON TRACK</span>
                                        @else
                                            <span class="badge bg-light text-secondary border">UNBUDGETED</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($catItem['has_budget'])
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="editBudget({{ $catItem['budget_id'] }})" title="Adjust Budget">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="quickCreateBudget('{{ $catItem['category_id'] }}', '{{ addslashes($catItem['category_name']) }}')" title="Quick Set Budget">
                                                <i class="bi bi-plus me-1"></i> Set Limit
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: BUDGET VS ACTUAL COMPARISON -->
        <div class="tab-pane fade {{ $activeTab === 'vs_actual' ? 'show active' : '' }}" id="vsActualTabPane" role="tabpanel">

            <!-- Comparative Visual Chart -->
            <div class="card-custom mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Budget vs. Actual Spending (Visual Variance)</h6>
                        <span class="text-muted small">Comparing planned budget allocations against actual expenditures for {{ ucfirst($period) }} ({{ $timeframe }}).</span>
                    </div>
                    <a href="{{ route('budgets.export', ['period' => $period, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-download me-1"></i> Export Data
                    </a>
                </div>
                <div class="card-body">
                    <div style="height: 320px; width: 100%;">
                        <canvas id="budgetVsActualChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Variance Table -->
            <div class="card-custom">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-dark">Variance Breakdown & Financial Performance</h6>
                    <span class="text-muted small">Favorable variances represent positive savings under target; unfavorable indicates overspend.</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category / Budget</th>
                                <th>Period</th>
                                <th>Planned Target</th>
                                <th>Spending Ceiling</th>
                                <th>Actual Spent</th>
                                <th>Variance ($)</th>
                                <th>Variance (%)</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categoryComparison as $item)
                                @if($item['has_budget'] || $item['spent'] > 0)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded p-1 text-white" style="background-color: {{ $item['color'] }}; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi {{ $item['icon'] }}" style="font-size: 0.85rem;"></i>
                                                </div>
                                                <span class="fw-semibold text-dark">{{ $item['category_name'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border">{{ ucfirst($period) }}</span>
                                        </td>
                                        <td>${{ number_format($item['budget_amount'], 2) }}</td>
                                        <td>${{ number_format($item['spending_limit'], 2) }}</td>
                                        <td class="fw-bold">${{ number_format($item['spent'], 2) }}</td>
                                        <td>
                                            <span class="fw-bold {{ $item['variance_amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $item['variance_amount'] >= 0 ? '+' : '' }}${{ number_format($item['variance_amount'], 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($item['variance_percentage'] !== null)
                                                <span class="badge {{ $item['variance_percentage'] > 0 ? 'bg-danger' : 'bg-success' }}">
                                                    {{ $item['variance_percentage'] > 0 ? '+' : '' }}{{ $item['variance_percentage'] }}%
                                                </span>
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item['status'] === 'exceeded')
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Unfavorable (Exceeded)</span>
                                            @elseif($item['status'] === 'warning')
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Caution (At Risk)</span>
                                            @elseif($item['has_budget'])
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Favorable (Under Budget)</span>
                                            @else
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">Unbudgeted</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>TOTAL SUMMARY</td>
                                <td>{{ ucfirst($period) }}</td>
                                <td>${{ number_format($summary['total_budgeted'], 2) }}</td>
                                <td>${{ number_format($summary['total_spending_limit'], 2) }}</td>
                                <td>${{ number_format($summary['total_spent'], 2) }}</td>
                                <td class="{{ $summary['net_variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $summary['net_variance'] >= 0 ? '+' : '' }}${{ number_format($summary['net_variance'], 2) }}
                                </td>
                                <td>
                                    @php
                                        $totalVariancePct = $summary['total_budgeted'] > 0 ? round((($summary['total_spent'] - $summary['total_budgeted']) / $summary['total_budgeted']) * 100, 1) : 0;
                                    @endphp
                                    <span class="badge {{ $totalVariancePct > 0 ? 'bg-danger' : 'bg-success' }}">
                                        {{ $totalVariancePct > 0 ? '+' : '' }}{{ $totalVariancePct }}%
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold {{ $summary['net_variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $summary['net_variance'] >= 0 ? 'Net Favorable' : 'Net Deficit' }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 4: ALERTS & THRESHOLDS -->
        <div class="tab-pane fade {{ $activeTab === 'alerts' ? 'show active' : '' }}" id="alertsTabPane" role="tabpanel">
            <div class="card-custom mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-dark">Active Budget Alerts & Threshold Monitors</h6>
                    <span class="text-muted small">Real-time alerts triggered by current period expenses against configured thresholds and caps.</span>
                </div>
                <div class="card-body">
                    @if(count($activeAlerts) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($activeAlerts as $alert)
                                <div class="list-group-item px-0 py-3 d-flex align-items-start justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="rounded-circle p-2 {{ $alert['severity'] === 'danger' ? 'bg-danger text-white' : 'bg-warning text-dark' }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                                            <i class="bi {{ $alert['severity'] === 'danger' ? 'bi-shield-fill-x' : 'bi-exclamation-triangle-fill' }} fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2">
                                                <h6 class="fw-bold mb-0 text-dark">{{ $alert['title'] }}: {{ $alert['budget']->name }}</h6>
                                                <span class="badge {{ $alert['severity'] === 'danger' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                                    {{ $alert['percent'] }}% Utilized
                                                </span>
                                            </div>
                                            <p class="text-muted mb-1 mt-1 small">{{ $alert['message'] }}</p>
                                            <div class="text-secondary small">
                                                <span>Category: <strong>{{ $alert['category_name'] }}</strong></span> &bull;
                                                <span>Threshold: <strong>{{ $alert['budget']->alert_threshold }}%</strong></span> &bull;
                                                <span>Effective Limit: <strong>${{ number_format($alert['limit'], 2) }}</strong></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editBudget({{ $alert['budget']->id }})">
                                            Adjust Budget
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-shield-check text-success fs-1 mb-2"></i>
                            <h6 class="fw-bold text-dark">All Budgets Within Safe Parameters</h6>
                            <p class="text-muted small mb-0">No active spending limits or threshold warnings for {{ ucfirst($period) }} ({{ $timeframe }}).</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Modal: New Budget -->
<div class="modal fade" id="newBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('budgets.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pie-chart text-primary me-2"></i>Create New Budget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Budget Name *</label>
                        <input type="text" name="name" id="new_name" class="form-control" placeholder="e.g. Monthly Cloud Stack & SaaS" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Category</label>
                            <select name="category_id" id="new_category_id" class="form-select">
                                <option value="">All Categories (General)</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Budget Period *</label>
                            <select name="period" id="new_period" class="form-select" onchange="togglePeriodInput('new')">
                                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Base Target Amount ($) *</label>
                            <input type="number" step="0.01" min="1" name="amount" id="new_amount" class="form-control" placeholder="1000.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Spending Limit Ceiling ($)</label>
                            <input type="number" step="0.01" min="1" name="spending_limit" id="new_spending_limit" class="form-control" placeholder="1200.00">
                            <div class="form-text small" style="font-size: 0.7rem;">Leave blank to match Base Target</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6" id="new_month_container">
                            <label class="form-label small fw-semibold">Effective Month</label>
                            <input type="month" name="month_year" id="new_month_input" class="form-control" value="{{ $selectedMonth }}">
                        </div>
                        <div class="col-md-6 d-none" id="new_year_container">
                            <label class="form-label small fw-semibold">Effective Year</label>
                            <input type="number" min="2020" max="2035" name="year_val" id="new_year_input" class="form-control" value="{{ $selectedYear }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Alert Threshold (%)</label>
                            <input type="number" min="10" max="100" name="alert_threshold" id="new_alert_threshold" class="form-control" value="80" required>
                            <div class="form-text small" style="font-size: 0.7rem;">Triggers alert when spending hits this %</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes / Guidelines (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Approved team budget cap for Q4 projects."></textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="notify_on_exceed" id="new_notify_on_exceed" value="1" checked>
                        <label class="form-check-label small" for="new_notify_on_exceed">Enable proactive threshold & spending limit warnings</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Create Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Budget -->
<div class="modal fade" id="editBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editBudgetForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Budget & Limits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Budget Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Category</label>
                            <select name="category_id" id="edit_category_id" class="form-select">
                                <option value="">All Categories (General)</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Budget Period *</label>
                            <select name="period" id="edit_period" class="form-select" onchange="togglePeriodInput('edit')">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Base Target Amount ($) *</label>
                            <input type="number" step="0.01" min="1" name="amount" id="edit_amount" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Spending Limit Ceiling ($)</label>
                            <input type="number" step="0.01" min="1" name="spending_limit" id="edit_spending_limit" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6" id="edit_month_container">
                            <label class="form-label small fw-semibold">Effective Month</label>
                            <input type="month" name="month_year" id="edit_month_input" class="form-control">
                        </div>
                        <div class="col-md-6 d-none" id="edit_year_container">
                            <label class="form-label small fw-semibold">Effective Year</label>
                            <input type="number" min="2020" max="2035" name="year_val" id="edit_year_input" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Alert Threshold (%)</label>
                            <input type="number" min="10" max="100" name="alert_threshold" id="edit_alert_threshold" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes / Guidelines</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="notify_on_exceed" id="edit_notify_on_exceed" value="1">
                        <label class="form-check-label small" for="edit_notify_on_exceed">Enable proactive threshold warnings</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Toggle between month input and year input
    function togglePeriodInput(prefix) {
        const periodSelect = document.getElementById(prefix + '_period');
        const monthContainer = document.getElementById(prefix + '_month_container');
        const yearContainer = document.getElementById(prefix + '_year_container');
        const monthInput = document.getElementById(prefix + '_month_input');
        const yearInput = document.getElementById(prefix + '_year_input');

        if (periodSelect.value === 'yearly') {
            monthContainer.classList.add('d-none');
            yearContainer.classList.remove('d-none');
            monthInput.removeAttribute('name');
            yearInput.setAttribute('name', 'month_year');
        } else {
            monthContainer.classList.remove('d-none');
            yearContainer.classList.add('d-none');
            monthInput.setAttribute('name', 'month_year');
            yearInput.removeAttribute('name');
        }
    }

    // Initialize toggle for creation form
    document.addEventListener('DOMContentLoaded', function() {
        togglePeriodInput('new');
    });

    // Quick Create Budget from category table
    function quickCreateBudget(catId, catName) {
        document.getElementById('new_category_id').value = catId;
        document.getElementById('new_name').value = catName + ' Spending Limit';
        const modal = new bootstrap.Modal(document.getElementById('newBudgetModal'));
        modal.show();
    }

    // Edit Budget modal loader
    function editBudget(id) {
        fetch('/budgets/' + id + '/edit', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.budget) {
                const b = data.budget;
                document.getElementById('editBudgetForm').action = '/budgets/' + b.id;
                document.getElementById('edit_name').value = b.name;
                document.getElementById('edit_category_id').value = b.category_id || '';
                document.getElementById('edit_period').value = b.period || 'monthly';
                document.getElementById('edit_amount').value = b.amount;
                document.getElementById('edit_spending_limit').value = b.spending_limit || b.amount;
                document.getElementById('edit_alert_threshold').value = b.alert_threshold || 80;
                document.getElementById('edit_notes').value = b.notes || '';
                document.getElementById('edit_notify_on_exceed').checked = Boolean(b.notify_on_exceed);

                if (b.period === 'yearly') {
                    document.getElementById('edit_year_input').value = b.month_year ? b.month_year.substring(0, 4) : '{{ $selectedYear }}';
                } else {
                    document.getElementById('edit_month_input').value = b.month_year || '{{ $selectedMonth }}';
                }

                togglePeriodInput('edit');

                const modal = new bootstrap.Modal(document.getElementById('editBudgetModal'));
                modal.show();
            }
        })
        .catch(err => {
            console.error('Error fetching budget details:', err);
            alert('Failed to load budget details.');
        });
    }

    // Chart.js Budget vs Actual Comparison
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('budgetVsActualChart');
        if (!ctx) return;

        const chartData = @json($chartData);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Target Budget ($)',
                        data: chartData.budget,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: 'Actual Spent ($)',
                        data: chartData.actual,
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
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
