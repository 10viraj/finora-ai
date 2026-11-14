@extends('layouts.app')

@section('title', 'AI Financial Advisor')
@section('page-title', 'AI Financial Assistant & Copilot')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Audit Actions -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-stars text-primary"></i> AI Financial Assistant
            </h4>
            <p class="text-muted small mb-0">Autonomous spending intelligence, anomaly detection, personalized savings, budget calibration, and natural language queries.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-secondary border px-2 py-1">
                <i class="bi bi-cpu me-1"></i>{{ $user->ai_model ?: 'gpt-4o-mini' }}
            </span>
            <a href="{{ route('ai.download-summary', ['month' => $selectedMonth]) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-download"></i>
                <span class="d-none d-sm-inline">Export Monthly Brief</span>
            </a>
            <form action="{{ route('ai.generate') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Run Financial Audit</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Health Score & Financial Pulse Ribbon -->
    <div class="row g-3 mb-4">
        <!-- Health Score -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Financial Health Score</div>
                    <span class="badge {{ $monthlySummary->health_score >= 80 ? 'bg-success' : ($monthlySummary->health_score >= 60 ? 'bg-warning text-dark' : 'bg-danger') }}">
                        {{ $monthlySummary->health_score >= 80 ? 'STRONG' : ($monthlySummary->health_score >= 60 ? 'FAIR' : 'NEEDS CARE') }}
                    </span>
                </div>
                <div class="d-flex align-items-baseline gap-2 mt-2">
                    <h3 class="fw-bold text-dark mb-0">{{ $monthlySummary->health_score }}</h3>
                    <span class="text-muted small">/ 100</span>
                </div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar {{ $monthlySummary->health_score >= 80 ? 'bg-success' : ($monthlySummary->health_score >= 60 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $monthlySummary->health_score }}%"></div>
                </div>
            </div>
        </div>

        <!-- Net Savings Rate -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Savings Retention</div>
                    <i class="bi bi-piggy-bank text-success"></i>
                </div>
                <h3 class="fw-bold {{ $savingsRate >= 0 ? 'text-success' : 'text-danger' }} mt-2 mb-0">
                    {{ $savingsRate >= 0 ? '+' : '' }}{{ $savingsRate }}%
                </h3>
                <div class="text-muted small mt-2">
                    Net: <strong class="{{ $monthlySummary->net_savings >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($monthlySummary->net_savings, 2) }}</strong>
                </div>
            </div>
        </div>

        <!-- Cashflow Runway -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Cashflow Runway</div>
                    <i class="bi bi-shield-check text-primary"></i>
                </div>
                <h3 class="fw-bold text-primary mt-2 mb-0">{{ $runwayMonths }} <span class="fs-6 text-muted fw-normal">Months</span></h3>
                <div class="text-muted small mt-2">
                    Liquid Reserves: <strong>${{ number_format($balance, 2) }}</strong>
                </div>
            </div>
        </div>

        <!-- Flagged Anomalies & Recs -->
        <div class="col-xl-3 col-sm-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small fw-semibold text-uppercase">Active Signals</div>
                    <span class="badge {{ count($anomalies) > 0 ? 'bg-danger' : 'bg-secondary' }}">
                        {{ count($anomalies) }} Outliers
                    </span>
                </div>
                <h3 class="fw-bold text-dark mt-2 mb-0">{{ count($allInsights) }}</h3>
                <div class="text-muted small mt-2">
                    <span>{{ count($budgetRecommendations) }} Budget Recs</span> &bull; 
                    <span>{{ count($savingSuggestions) }} Saving Tips</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive AI Financial Copilot (Ask Questions About Your Spending) -->
    <div class="card-custom mb-4" style="border-left: 4px solid #2563eb;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-chat-left-dots-fill"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Ask Questions About Your Spending</h6>
                        <span class="text-muted small">Chat with FinTrack AI about category spend, largest transactions, budget status, or runway.</span>
                    </div>
                </div>
                @if(count($chatMessages) > 0)
                    <form action="{{ route('ai.clear-chat') }}" method="POST" class="m-0" onsubmit="return confirm('Clear chat history?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-link text-muted text-decoration-none">
                            <i class="bi bi-trash me-1"></i>Clear History
                        </button>
                    </form>
                @endif
            </div>

            <!-- Chat Conversation Thread (Scrollable) -->
            <div id="chatThreadContainer" class="p-3 mb-3 bg-light rounded border" style="max-height: 280px; overflow-y: auto;">
                @forelse($chatMessages as $msg)
                    <div class="d-flex mb-3 {{ $msg->role === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                        <div class="p-3 rounded shadow-xs" style="max-width: 80%; {{ $msg->role === 'user' ? 'background: #2563eb; color: #fff;' : 'background: #fff; color: #1e293b; border: 1px solid #e2e8f0;' }}">
                            <div class="d-flex align-items-center gap-1 mb-1 font-monospace" style="font-size: 0.7rem; opacity: 0.85;">
                                <i class="bi {{ $msg->role === 'user' ? 'bi-person-fill' : 'bi-stars text-primary' }}"></i>
                                <span>{{ $msg->role === 'user' ? 'You' : 'FinTrack AI' }}</span> &bull;
                                <span>{{ $msg->created_at->format('g:i A') }}</span>
                            </div>
                            <div style="font-size: 0.875rem; line-height: 1.5; white-space: pre-wrap;">{{ $msg->message }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3 text-muted small" id="emptyChatPlaceholder">
                        <i class="bi bi-chat-quote fs-3 d-block mb-1 text-secondary"></i>
                        No conversations yet. Ask a question below or pick a suggested query to test your financial copilot!
                    </div>
                @endforelse
            </div>

            <!-- Query Input Form -->
            <form id="aiAskForm" onsubmit="askAdvisor(event)">
                <div class="input-group">
                    <input type="text" id="aiQueryInput" class="form-control" placeholder="e.g. How much did I spend on Software and Subscriptions this month?" required autocomplete="off">
                    <button class="btn btn-primary px-4 fw-semibold d-flex align-items-center gap-1" type="submit" id="aiSubmitBtn">
                        <i class="bi bi-send-fill"></i>
                        <span>Ask AI</span>
                    </button>
                </div>
            </form>

            <!-- Suggested Query Pills -->
            <div class="d-flex gap-2 mt-3 flex-wrap" style="font-size: 0.775rem;">
                <span class="text-muted fw-semibold">Frequently asked:</span>
                <a href="javascript:void(0)" class="badge bg-white text-primary border text-decoration-none shadow-xs py-1 px-2" onclick="setQuery('How much did I spend this month?')">
                    <i class="bi bi-wallet2 me-1"></i>How much did I spend this month?
                </a>
                <a href="javascript:void(0)" class="badge bg-white text-primary border text-decoration-none shadow-xs py-1 px-2" onclick="setQuery('Which category has the highest expenses?')">
                    <i class="bi bi-pie-chart-fill me-1"></i>Which category has the highest expenses?
                </a>
                <a href="javascript:void(0)" class="badge bg-white text-primary border text-decoration-none shadow-xs py-1 px-2" onclick="setQuery('How can I save ₹5,000 next month?')">
                    <i class="bi bi-piggy-bank-fill me-1"></i>How can I save ₹5,000 next month?
                </a>
                <a href="javascript:void(0)" class="badge bg-white text-primary border text-decoration-none shadow-xs py-1 px-2" onclick="setQuery('Compare my expenses with last month.')">
                    <i class="bi bi-arrow-left-right me-1"></i>Compare my expenses with last month.
                </a>
            </div>
        </div>
    </div>

    <!-- AI Spending Analysis & Budget Recommendations Dual Cards -->
    <div class="row g-3 mb-4">
        <!-- AI Spending Analysis -->
        <div class="col-lg-6">
            <div class="card-custom h-100" style="border-top: 3px solid #6366f1;">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded">
                            <i class="bi bi-graph-up-arrow fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">AI Spending Analysis</h6>
                            <span class="text-muted small">Live pattern detection from authorized ledger records</span>
                        </div>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1">Pattern Radar</span>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush">
                        @if(isset($aiSummary['spending_analysis']) && count($aiSummary['spending_analysis']) > 0)
                            @foreach($aiSummary['spending_analysis'] as $idx => $point)
                                @php
                                    $icons = ['bi-graph-up text-danger', 'bi-trophy text-warning', 'bi-percent text-info', 'bi-shield-check text-success'];
                                    $icon = $icons[$idx % 4];
                                @endphp
                                <li class="list-group-item px-2 py-2 d-flex align-items-start gap-2 bg-transparent">
                                    <i class="bi {{ $icon }} mt-1 fs-6"></i>
                                    <div class="text-dark small fw-medium">{{ $point }}</div>
                                </li>
                            @endforeach
                        @else
                            <li class="list-group-item px-2 py-2 d-flex align-items-center gap-2 bg-transparent">
                                <i class="bi bi-graph-up text-danger"></i>
                                <span class="small fw-medium">Food expenses increased by 20%.</span>
                            </li>
                            <li class="list-group-item px-2 py-2 d-flex align-items-center gap-2 bg-transparent">
                                <i class="bi bi-trophy text-warning"></i>
                                <span class="small fw-medium">Shopping is your highest expense category.</span>
                            </li>
                            <li class="list-group-item px-2 py-2 d-flex align-items-center gap-2 bg-transparent">
                                <i class="bi bi-percent text-info"></i>
                                <span class="small fw-medium">Your savings rate decreased this month.</span>
                            </li>
                            <li class="list-group-item px-2 py-2 d-flex align-items-center gap-2 bg-transparent">
                                <i class="bi bi-shield-check text-success"></i>
                                <span class="small fw-medium">Your transport expenses are within budget.</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <!-- AI Budget Recommendations -->
        <div class="col-lg-6">
            <div class="card-custom h-100" style="border-top: 3px solid #10b981;">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded">
                            <i class="bi bi-sliders fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">AI Budget Recommendations</h6>
                            <span class="text-muted small">Surplus optimization & savings calibration</span>
                        </div>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">Smart Advisor</span>
                </div>
                <div class="card-body p-3">
                    @php
                        $bRec = $aiSummary['budget_recommendation'] ?? [
                            'formatted' => ['income' => '₹40,000', 'expenses' => '₹32,000', 'savings' => '₹8,000', 'target' => '₹10,000'],
                            'ai_suggestion' => 'Review discretionary spending and consider setting a monthly savings target of ₹10,000.',
                        ];
                    @endphp
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-4">
                            <div class="p-2 bg-light rounded border">
                                <div class="text-muted" style="font-size: 0.7rem;">MONTHLY INCOME</div>
                                <div class="fw-bold text-dark mt-1">{{ $bRec['formatted']['income'] }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded border">
                                <div class="text-muted" style="font-size: 0.7rem;">MONTHLY EXPENSES</div>
                                <div class="fw-bold text-danger mt-1">{{ $bRec['formatted']['expenses'] }}</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded border">
                                <div class="text-muted" style="font-size: 0.7rem;">NET SAVINGS</div>
                                <div class="fw-bold text-success mt-1">{{ $bRec['formatted']['savings'] }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 bg-light rounded border-start border-success border-3 mb-2">
                        <div class="d-flex align-items-center gap-1 text-success fw-bold small mb-1">
                            <i class="bi bi-lightbulb-fill"></i> AI Suggestion:
                        </div>
                        <div class="text-dark small lh-sm">
                            {{ $bRec['ai_suggestion'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for AI Financial Assistant Modules -->
    <ul class="nav nav-pills mb-4 bg-light p-1 rounded border" id="aiTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overviewTabPane" type="button" role="tab">
                <i class="bi bi-grid-fill me-1"></i> All Insights ({{ count($allInsights) }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'anomalies' ? 'active' : '' }}" id="anomalies-tab" data-bs-toggle="tab" data-bs-target="#anomaliesTabPane" type="button" role="tab">
                <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Unusual Expenses
                @if(count($anomalies) > 0)
                    <span class="badge bg-danger rounded-pill ms-1">{{ count($anomalies) }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'savings' ? 'active' : '' }}" id="savings-tab" data-bs-toggle="tab" data-bs-target="#savingsTabPane" type="button" role="tab">
                <i class="bi bi-piggy-bank-fill text-success me-1"></i> Saving Suggestions ({{ count($savingSuggestions) }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'budget_recs' ? 'active' : '' }}" id="budgetrecs-tab" data-bs-toggle="tab" data-bs-target="#budgetRecsTabPane" type="button" role="tab">
                <i class="bi bi-sliders me-1"></i> Budget Recommendations ({{ count($budgetRecommendations) }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'monthly_summary' ? 'active' : '' }}" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summaryTabPane" type="button" role="tab">
                <i class="bi bi-file-earmark-text-fill text-info me-1"></i> Monthly Summaries
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="aiTabContent">

        <!-- TAB 1: ALL INSIGHTS OVERVIEW -->
        <div class="tab-pane fade {{ $activeTab === 'overview' ? 'show active' : '' }}" id="overviewTabPane" role="tabpanel">
            <div class="row g-3">
                @forelse($allInsights as $item)
                    @php
                        $typeMeta = [
                            'spending_anomaly' => ['icon' => 'bi-graph-up-arrow', 'color' => 'text-danger', 'badge' => 'bg-danger', 'label' => 'ANOMALY'],
                            'savings_opportunity' => ['icon' => 'bi-piggy-bank', 'color' => 'text-success', 'badge' => 'bg-success', 'label' => 'SAVINGS'],
                            'budget_recommendation' => ['icon' => 'bi-sliders', 'color' => 'text-primary', 'badge' => 'bg-primary', 'label' => 'BUDGET REC'],
                            'budget_warning' => ['icon' => 'bi-exclamation-octagon', 'color' => 'text-warning', 'badge' => 'bg-warning text-dark', 'label' => 'BUDGET ALERT'],
                            'cashflow_forecast' => ['icon' => 'bi-calendar-check', 'color' => 'text-info', 'badge' => 'bg-info text-dark', 'label' => 'RUNWAY'],
                        ];
                        $meta = $typeMeta[$item->type] ?? ['icon' => 'bi-stars', 'color' => 'text-primary', 'badge' => 'bg-secondary', 'label' => 'INSIGHT'];
                    @endphp
                    <div class="col-lg-6">
                        <div class="card-custom h-100 d-flex flex-column justify-content-between shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi {{ $meta['icon'] }} {{ $meta['color'] }} fs-4"></i>
                                        <h6 class="fw-bold mb-0 text-dark">{{ $item->title }}</h6>
                                    </div>
                                    <span class="badge {{ $meta['badge'] }} px-2 py-1 text-uppercase" style="font-size: 0.65rem;">
                                        {{ $meta['label'] }}
                                    </span>
                                </div>

                                <p class="text-secondary small mb-3">
                                    {{ $item->summary }}
                                </p>

                                @if($item->detailed_analysis)
                                    <div class="p-2 bg-light rounded small text-muted mb-3" style="font-size: 0.8rem; border-left: 3px solid #cbd5e1;">
                                        {{ $item->detailed_analysis }}
                                    </div>
                                @endif

                                @if($item->action_suggested)
                                    <div class="d-flex align-items-start gap-2 text-primary small fw-semibold">
                                        <i class="bi bi-arrow-right-circle-fill mt-1"></i>
                                        <span>Action: {{ $item->action_suggested }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="card-footer bg-white border-top py-2 px-3 d-flex align-items-center justify-content-between text-muted small">
                                <span>{{ $item->created_at->diffForHumans() }}</span>
                                <div class="d-flex align-items-center gap-2">
                                    @if($item->type === 'budget_recommendation')
                                        <form action="{{ route('ai.apply', $item) }}" method="POST" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm py-0 px-2">
                                                <i class="bi bi-check-lg me-1"></i>Apply Budget
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('ai.dismiss', $item) }}" method="POST" class="m-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-link btn-sm text-muted p-0 text-decoration-none">
                                            <i class="bi bi-x-circle me-1"></i>Dismiss
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card-custom text-center py-5">
                            <i class="bi bi-check2-circle fs-1 text-success mb-2"></i>
                            <h5>No Active Insights</h5>
                            <p class="text-muted small mb-3">All spending signals and budget parameters are within baseline targets.</p>
                            <form action="{{ route('ai.generate') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-arrow-repeat me-1"></i> Run Financial Audit
                                </button>
                            </form>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- TAB 2: UNUSUAL EXPENSE DETECTION (ANOMALIES) -->
        <div class="tab-pane fade {{ $activeTab === 'anomalies' ? 'show active' : '' }}" id="anomaliesTabPane" role="tabpanel">
            <div class="card-custom">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Unusual Expense Detection & Anomalies</h6>
                        <span class="text-muted small">Algorithmic detection of single outlays >30% of total spend, category multipliers >2.5x baseline, or rapid velocity surges.</span>
                    </div>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">
                        {{ count($anomalies) }} Flagged Anomaly{{ count($anomalies) === 1 ? '' : 'ies' }}
                    </span>
                </div>
                <div class="card-body">
                    @forelse($anomalies as $anomaly)
                        <div class="p-3 mb-3 rounded border border-danger border-opacity-25 bg-danger bg-opacity-10">
                            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-danger text-white rounded p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0 text-danger">{{ $anomaly->title }}</h6>
                                </div>
                                <form action="{{ route('ai.dismiss', $anomaly) }}" method="POST" class="m-0">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                        <i class="bi bi-check2 me-1"></i>Mark Reviewed
                                    </button>
                                </form>
                            </div>
                            <p class="text-dark small mb-2">{{ $anomaly->summary }}</p>
                            <div class="p-2 bg-white rounded small text-secondary mb-2" style="font-size: 0.8rem;">
                                <strong>AI Anomaly Diagnosis:</strong> {{ $anomaly->detailed_analysis }}
                            </div>
                            <div class="d-flex align-items-center gap-2 text-primary small fw-semibold">
                                <i class="bi bi-shield-exclamation"></i>
                                <span>Remediation: {{ $anomaly->action_suggested }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="bi bi-shield-check text-success fs-1 mb-2"></i>
                            <h6 class="fw-bold text-dark">No Unusual Expenses Detected</h6>
                            <p class="text-muted small mb-0">All current transactions align within normal category distributions and historical standard deviations.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- TAB 3: PERSONALIZED SAVING SUGGESTIONS -->
        <div class="tab-pane fade {{ $activeTab === 'savings' ? 'show active' : '' }}" id="savingsTabPane" role="tabpanel">
            <div class="card-custom">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-dark">Personalized Cost Optimization & Savings Suggestions</h6>
                    <span class="text-muted small">Data-driven recommendations tailored to your profile ({{ $user->role === 'small_business' ? 'Small Business' : 'Personal' }}) to maximize operational runway.</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @forelse($savingSuggestions as $tip)
                            <div class="col-lg-6">
                                <div class="card border rounded h-100 shadow-xs">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="bg-success bg-opacity-10 text-success p-2 rounded" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-piggy-bank-fill fs-5"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-0">{{ $tip->title }}</h6>
                                        </div>
                                        <p class="text-secondary small mb-2">{{ $tip->summary }}</p>
                                        <div class="p-2 bg-light rounded text-muted small mb-3" style="font-size: 0.8rem;">
                                            {{ $tip->detailed_analysis }}
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                            <span class="text-primary small fw-semibold">
                                                <i class="bi bi-check-circle-fill me-1"></i>{{ $tip->action_suggested }}
                                            </span>
                                            <form action="{{ route('ai.dismiss', $tip) }}" method="POST" class="m-0">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-link btn-sm text-muted p-0 text-decoration-none">
                                                    Dismiss
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5">
                                <i class="bi bi-lightning-charge text-warning fs-1 mb-2"></i>
                                <h6 class="fw-bold">No Saving Opportunities Generated Yet</h6>
                                <p class="text-muted small mb-3">Click below to run a financial audit across your recent transactions.</p>
                                <form action="{{ route('ai.generate') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-arrow-repeat me-1"></i> Run Audit
                                    </button>
                                </form>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: BUDGET RECOMMENDATIONS -->
        <div class="tab-pane fade {{ $activeTab === 'budget_recs' ? 'show active' : '' }}" id="budgetRecsTabPane" role="tabpanel">
            <div class="card-custom">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Automated Budget Calibration & Recommendations</h6>
                        <span class="text-muted small">Proposals for unbudgeted active categories and recalibration of repeatedly breached targets.</span>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1">
                        {{ count($budgetRecommendations) }} Available Recommendation{{ count($budgetRecommendations) === 1 ? '' : 's' }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @forelse($budgetRecommendations as $rec)
                            @php
                                $snap = $rec->metrics_snapshot ?? [];
                            @endphp
                            <div class="col-lg-6">
                                <div class="card border rounded h-100 shadow-xs">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-sliders fs-5"></i>
                                                </div>
                                                <h6 class="fw-bold text-dark mb-0">{{ $rec->title }}</h6>
                                            </div>
                                            <span class="badge bg-light text-secondary border">Monthly</span>
                                        </div>
                                        <p class="text-secondary small mb-2">{{ $rec->summary }}</p>
                                        
                                        @if(!empty($snap))
                                            <div class="row g-2 p-2 bg-light rounded text-center mb-3">
                                                <div class="col-4 border-end">
                                                    <div class="text-muted" style="font-size: 0.68rem;">CURRENT SPEND</div>
                                                    <strong class="text-dark">${{ number_format($snap['current_spent'] ?? 0, 2) }}</strong>
                                                </div>
                                                <div class="col-4 border-end">
                                                    <div class="text-muted" style="font-size: 0.68rem;">SUGGESTED TARGET</div>
                                                    <strong class="text-primary">${{ number_format($snap['suggested_amount'] ?? 0, 2) }}</strong>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-muted" style="font-size: 0.68rem;">SPENDING CAP</div>
                                                    <strong class="text-secondary">${{ number_format($snap['suggested_limit'] ?? 0, 2) }}</strong>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="p-2 bg-light rounded small text-muted mb-3" style="font-size: 0.8rem;">
                                            {{ $rec->detailed_analysis }}
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                            <form action="{{ route('ai.dismiss', $rec) }}" method="POST" class="m-0">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-link btn-sm text-muted p-0 text-decoration-none">
                                                    Ignore
                                                </button>
                                            </form>
                                            <form action="{{ route('ai.apply', $rec) }}" method="POST" class="m-0">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                                    <i class="bi bi-check-lg me-1"></i> Apply Recommendation
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-5">
                                <i class="bi bi-check-circle text-success fs-1 mb-2"></i>
                                <h6 class="fw-bold">All Expense Categories Properly Budgeted</h6>
                                <p class="text-muted small mb-0">No unbudgeted spending categories or misaligned targets detected.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 5: MONTHLY FINANCIAL SUMMARIES -->
        <div class="tab-pane fade {{ $activeTab === 'monthly_summary' ? 'show active' : '' }}" id="summaryTabPane" role="tabpanel">
            <div class="card-custom mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Executive Monthly Financial Summary</h6>
                        <span class="text-muted small">Generated narrative overview, cash flow performance metrics, and strategic takeaways.</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <form action="{{ route('ai.index') }}" method="GET" class="d-flex align-items-center gap-2 m-0">
                            <input type="hidden" name="tab" value="monthly_summary">
                            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($availableMonths as $m)
                                    <option value="{{ $m }}" {{ $selectedMonth === $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::createFromFormat('Y-m', $m)->format('F Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <a href="{{ route('ai.download-summary', ['month' => $selectedMonth]) }}" class="btn btn-outline-secondary btn-sm" title="Download Text Summary">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Cash Flow Metric Pill Grid -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <div class="text-muted small fw-semibold">RECOGNIZED REVENUE</div>
                                <h4 class="fw-bold text-success mt-1 mb-0">${{ number_format($monthlySummary->total_income, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <div class="text-muted small fw-semibold">TOTAL DISBURSEMENTS</div>
                                <h4 class="fw-bold text-danger mt-1 mb-0">${{ number_format($monthlySummary->total_expenses, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <div class="text-muted small fw-semibold">NET CASH FLOW</div>
                                <h4 class="fw-bold {{ $monthlySummary->net_savings >= 0 ? 'text-success' : 'text-danger' }} mt-1 mb-0">
                                    ${{ number_format($monthlySummary->net_savings, 2) }}
                                </h4>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <div class="text-muted small fw-semibold">SAVINGS RETENTION RATE</div>
                                <h4 class="fw-bold text-primary mt-1 mb-0">{{ $monthlySummary->savings_rate }}%</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Executive Narrative -->
                    <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text text-primary"></i> Executive Financial Brief
                    </h6>
                    <div class="p-3 bg-light rounded border mb-4" style="line-height: 1.7; font-size: 0.925rem;">
                        {{ $monthlySummary->executive_summary }}
                    </div>

                    <!-- Top Category Expense Drivers -->
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-dark mb-3">Top Expense Drivers for {{ $monthlySummary->formatted_month }}</h6>
                            @if(!empty($monthlySummary->top_categories))
                                @foreach($monthlySummary->top_categories as $cat)
                                    @php
                                        $pct = $monthlySummary->total_expenses > 0 ? round(($cat['total'] / $monthlySummary->total_expenses) * 100, 1) : 0;
                                    @endphp
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between small fw-semibold mb-1">
                                            <span>{{ $cat['category_name'] }}</span>
                                            <span class="text-muted">${{ number_format($cat['total'], 2) }} ({{ $pct }}%)</span>
                                        </div>
                                        <div class="progress" style="height: 7px;">
                                            <div class="progress-bar" style="width: {{ $pct }}%; background-color: {{ $cat['color'] ?? '#3b82f6' }}"></div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-muted small">No category expense records found for this period.</div>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold text-dark mb-3">Key Focus Areas for Next Month</h6>
                            <div class="list-group list-group-flush border rounded">
                                @if(!empty($monthlySummary->recommendations))
                                    @foreach($monthlySummary->recommendations as $rec)
                                        <div class="list-group-item d-flex align-items-start gap-2 py-3">
                                            <i class="bi bi-check2-circle text-primary fs-5 mt-n1"></i>
                                            <span class="small text-secondary">{{ $rec }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
function setQuery(text) {
    document.getElementById('aiQueryInput').value = text;
    document.getElementById('aiQueryInput').focus();
}

function askAdvisor(e) {
    e.preventDefault();
    const queryInput = document.getElementById('aiQueryInput');
    const query = queryInput.value.trim();
    if (!query) return;

    const btn = document.getElementById('aiSubmitBtn');
    const chatContainer = document.getElementById('chatThreadContainer');
    const emptyPlaceholder = document.getElementById('emptyChatPlaceholder');

    if (emptyPlaceholder) {
        emptyPlaceholder.remove();
    }

    // Append User Message to Thread
    const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const userBubble = document.createElement('div');
    userBubble.className = 'd-flex mb-3 justify-content-end';
    userBubble.innerHTML = `
        <div class="p-3 rounded shadow-xs" style="max-width: 80%; background: #2563eb; color: #fff;">
            <div class="d-flex align-items-center gap-1 mb-1 font-monospace" style="font-size: 0.7rem; opacity: 0.85;">
                <i class="bi bi-person-fill"></i>
                <span>You</span> &bull; <span>${nowTime}</span>
            </div>
            <div style="font-size: 0.875rem; line-height: 1.5; white-space: pre-wrap;">${escapeHtml(query)}</div>
        </div>
    `;
    chatContainer.appendChild(userBubble);
    chatContainer.scrollTop = chatContainer.scrollHeight;

    // Clear input & disable button
    queryInput.value = '';
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Analyzing...';

    // Temporary Assistant Loading Bubble
    const loadingBubble = document.createElement('div');
    loadingBubble.id = 'aiLoadingBubble';
    loadingBubble.className = 'd-flex mb-3 justify-content-start';
    loadingBubble.innerHTML = `
        <div class="p-3 rounded shadow-xs" style="max-width: 80%; background: #fff; color: #64748b; border: 1px solid #e2e8f0;">
            <div class="d-flex align-items-center gap-2 small">
                <span class="spinner-border spinner-border-sm text-primary"></span>
                <span>FinTrack AI is analyzing your transactions...</span>
            </div>
        </div>
    `;
    chatContainer.appendChild(loadingBubble);
    chatContainer.scrollTop = chatContainer.scrollHeight;

    fetch('{{ route('ai.ask') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ query: query })
    })
    .then(res => res.json())
    .then(data => {
        loadingBubble.remove();

        const aiBubble = document.createElement('div');
        aiBubble.className = 'd-flex mb-3 justify-content-start';
        aiBubble.innerHTML = `
            <div class="p-3 rounded shadow-xs" style="max-width: 80%; background: #fff; color: #1e293b; border: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-1 mb-1 font-monospace" style="font-size: 0.7rem; opacity: 0.85;">
                    <i class="bi bi-stars text-primary"></i>
                    <span>FinTrack AI</span> &bull; <span>${nowTime}</span>
                </div>
                <div style="font-size: 0.875rem; line-height: 1.5; white-space: pre-wrap;">${escapeHtml(data.answer)}</div>
            </div>
        `;
        chatContainer.appendChild(aiBubble);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    })
    .catch(err => {
        loadingBubble.remove();
        alert('An error occurred while consulting the AI financial assistant.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i><span>Ask AI</span>';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>
@endpush
@endsection
