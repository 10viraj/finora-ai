@extends('layouts.app')

@section('title', 'Subscription Plans & SaaS Quotas - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Subscription & Usage Plans</h1>
            <p class="text-secondary small mb-0">Manage your SaaS tier, AI daily query quotas, and enterprise capabilities.</p>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-2 fs-6 text-uppercase">Current Tier: {{ $subscription['plan_name'] }}</span>
        </div>
    </div>

    <!-- Usage Quota Progress Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="fw-bold mb-1">Daily AI Financial Query Quota</h5>
                    <p class="text-muted small mb-0">Resets daily at 00:00 UTC</p>
                </div>
                <div class="col-md-5">
                    @php
                        $limit = $subscription['daily_ai_limit'];
                        $used = $subscription['daily_ai_used'];
                        $pct = $limit > 0 ? min(100, round(($used / $limit) * 100)) : 0;
                    @endphp
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span>Used: {{ $used }} queries</span>
                        <span>Limit: {{ $limit === -1 ? 'Unlimited' : $limit . ' queries' }}</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : 'bg-primary' }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                <div class="col-md-3 text-md-end mt-3 mt-md-0">
                    <span class="text-success fw-bold">{{ $subscription['daily_ai_remaining'] === 999999 ? 'Unlimited Available' : $subscription['daily_ai_remaining'] . ' Remaining' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Grid -->
    <div class="row g-4">
        @foreach($plans as $key => $plan)
            @php
                $isCurrent = $subscription['plan_id'] === $key;
            @endphp
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm {{ $isCurrent ? 'border-2 border-primary' : '' }}">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold mb-0">{{ $plan['name'] }}</h4>
                            @if($isCurrent)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active Plan</span>
                            @endif
                        </div>
                        <div class="my-3">
                            <span class="display-6 fw-bold">₹{{ number_format($plan['price_inr']) }}</span>
                            <span class="text-muted">/ month (or ${{ number_format($plan['price_usd'], 2) }})</span>
                        </div>
                        <ul class="list-unstyled my-4 flex-grow-1">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> {{ $plan['max_accounts'] === -1 ? 'Unlimited Accounts' : 'Up to ' . $plan['max_accounts'] . ' Accounts' }}</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> {{ $plan['max_transactions_monthly'] === -1 ? 'Unlimited Monthly Transactions' : 'Up to ' . $plan['max_transactions_monthly'] . ' Transactions/mo' }}</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> {{ $plan['daily_ai_queries_limit'] === -1 ? 'Unlimited Daily AI Queries' : $plan['daily_ai_queries_limit'] . ' Daily AI Queries' }}</li>
                            <li class="mb-2"><i class="bi bi-{{ $plan['features']['receipt_ocr_scanning'] ? 'check-circle-fill text-success' : 'x-circle text-muted' }} me-2"></i> Receipt OCR Scanning</li>
                            <li class="mb-2"><i class="bi bi-{{ $plan['features']['recurring_automation'] ? 'check-circle-fill text-success' : 'x-circle text-muted' }} me-2"></i> Recurring Expense Automation</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Statistical Anomaly Detection</li>
                            <li class="mb-2"><i class="bi bi-{{ $plan['features']['audit_trail'] ? 'check-circle-fill text-success' : 'x-circle text-muted' }} me-2"></i> Comprehensive Audit Trail</li>
                        </ul>

                        <form action="{{ route('subscriptions.upgrade') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $key }}">
                            <button type="submit" class="btn w-100 {{ $isCurrent ? 'btn-outline-secondary' : 'btn-primary' }}" {{ $isCurrent ? 'disabled' : '' }}>
                                {{ $isCurrent ? 'Current Plan' : 'Switch to ' . $plan['name'] }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

