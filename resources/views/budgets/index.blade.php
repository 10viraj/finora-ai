@extends('layouts.app')

@section('title', 'Budgets')
@section('page-title', 'Budget Management')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Month Filter Row -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Expense Budgets</h4>
            <p class="text-muted small mb-0">Set spending limits and receive automated alerts before exceeding targets.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('budgets.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <input type="month" name="month" class="form-control form-control-sm" value="{{ $selectedMonth }}" onchange="this.form.submit()">
            </form>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newBudgetModal">
                <i class="bi bi-plus-lg me-1"></i> New Budget
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Total Budget</div>
                <h3 class="fw-bold text-dark mt-2 mb-0">${{ number_format($totalBudgeted, 2) }}</h3>
                <div class="text-muted small mt-2">{{ count($budgets) }} Active Budgets</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Total Spent</div>
                <h3 class="fw-bold text-danger mt-2 mb-0">${{ number_format($totalSpent, 2) }}</h3>
                <div class="text-muted small mt-2">Overall: {{ $overallPercentage }}% spent</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Remaining Pool</div>
                <h3 class="fw-bold {{ $totalRemaining >= 0 ? 'text-success' : 'text-danger' }} mt-2 mb-0">
                    ${{ number_format($totalRemaining, 2) }}
                </h3>
                <div class="text-muted small mt-2">{{ $totalRemaining >= 0 ? 'Within target' : 'Over budget' }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Overall Utilization</div>
                <h3 class="fw-bold {{ $overallPercentage > 100 ? 'text-danger' : ($overallPercentage >= 80 ? 'text-warning' : 'text-primary') }} mt-2 mb-0">
                    {{ $overallPercentage }}%
                </h3>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar {{ $overallPercentage > 100 ? 'bg-danger' : ($overallPercentage >= 80 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ min(100, $overallPercentage) }}%"></div>
                </div>
            </div>
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
                <div class="card-custom h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-light rounded p-2 text-primary">
                                    <i class="bi {{ $b->category->icon ?? 'bi-pie-chart' }} fs-5" style="color: {{ $b->category->color ?? '#2563eb' }}"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">{{ $b->name }}</h6>
                                    <span class="text-muted small">{{ $b->category->name ?? 'General Spending' }}</span>
                                </div>
                            </div>
                            <span class="badge {{ $badgeClass }} px-2 py-1 text-uppercase" style="font-size: 0.65rem;">
                                {{ $b->status }}
                            </span>
                        </div>

                        <div class="d-flex align-items-baseline justify-content-between mb-2">
                            <div>
                                <span class="fs-4 fw-bold text-dark">${{ number_format($b->spent, 2) }}</span>
                                <span class="text-muted small"> / ${{ number_format($b->amount, 2) }}</span>
                            </div>
                            <span class="fw-bold {{ $isExceeded ? 'text-danger' : 'text-muted' }} small">
                                {{ $b->utilization_percentage }}%
                            </span>
                        </div>

                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar {{ $barClass }}" role="progressbar" style="width: {{ $pct }}%"></div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top text-muted small">
                            <span>
                                Remaining: <strong class="{{ $b->remaining < 0 ? 'text-danger' : 'text-success' }}">${{ number_format($b->remaining, 2) }}</strong>
                            </span>
                            <form action="{{ route('budgets.destroy', $b) }}" method="POST" onsubmit="return confirm('Delete this budget?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-link text-danger p-0 text-decoration-none small">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card-custom text-center py-5">
                    <i class="bi bi-calculator fs-1 text-secondary mb-2"></i>
                    <h5>No Budgets Defined Yet</h5>
                    <p class="text-muted small mb-3">Create budgets to gain control over category spending and get automatic warnings.</p>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newBudgetModal">
                        <i class="bi bi-plus-lg me-1"></i> Create First Budget
                    </button>
                </div>
            </div>
        @endforelse
    </div>

</div>

<!-- Modal: New Budget -->
<div class="modal fade" id="newBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('budgets.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pie-chart text-primary me-2"></i>Create Expense Budget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Budget Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Monthly SaaS & Cloud Stack" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">All Categories (General)</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Budget Limit ($) *</label>
                            <input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="1000.00" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Month</label>
                            <input type="month" name="month_year" class="form-control" value="{{ $selectedMonth }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Alert Threshold (%)</label>
                            <input type="number" min="10" max="100" name="alert_threshold" class="form-control" value="80" required>
                            <div class="form-text small" style="font-size: 0.7rem;">Trigger warning when spending crosses this %</div>
                        </div>
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
@endsection
