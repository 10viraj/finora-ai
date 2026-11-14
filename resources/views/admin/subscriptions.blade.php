@extends('layouts.app')

@section('title', 'Admin - Subscription Plans - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Admin Console</span>
                <h1 class="h3 fw-bold mb-0">Subscription Plans & SaaS Tiers</h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">Configure pricing tiers, daily query quotas, account allowances, and feature access.</p>
        </div>
        <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Overview
        </a>
    </div>

    <!-- Active Subscribers Breakdown -->
    <div class="row g-4 mb-4">
        @foreach($plans as $key => $plan)
            @php
                $subCount = $subscribersByPlan[$key] ?? 0;
            @endphp
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="fw-bold mb-0">{{ $plan['name'] }}</h4>
                            <span class="badge bg-primary text-uppercase">{{ $subCount }} Users</span>
                        </div>
                        <div class="my-3">
                            <span class="fs-2 fw-bold">₹{{ number_format($plan['price_inr']) }}</span>
                            <span class="text-muted">/mo (${{ number_format($plan['price_usd'], 2) }})</span>
                        </div>
                        <ul class="list-unstyled small mb-4 flex-grow-1">
                            <li class="mb-2"><i class="bi bi-check text-success fs-5 align-middle"></i> <strong>Accounts:</strong> {{ $plan['max_accounts'] === -1 ? 'Unlimited' : 'Up to ' . $plan['max_accounts'] }}</li>
                            <li class="mb-2"><i class="bi bi-check text-success fs-5 align-middle"></i> <strong>Transactions:</strong> {{ $plan['max_transactions_monthly'] === -1 ? 'Unlimited' : $plan['max_transactions_monthly'] . '/mo' }}</li>
                            <li class="mb-2"><i class="bi bi-check text-success fs-5 align-middle"></i> <strong>Daily AI Queries:</strong> {{ $plan['daily_ai_queries_limit'] === -1 ? 'Unlimited' : $plan['daily_ai_queries_limit'] . ' queries/day' }}</li>
                            <li class="mb-2"><i class="bi bi-check text-success fs-5 align-middle"></i> <strong>Receipt OCR:</strong> {{ $plan['features']['receipt_ocr_scanning'] ? 'Enabled' : 'Disabled' }}</li>
                            <li class="mb-2"><i class="bi bi-check text-success fs-5 align-middle"></i> <strong>Recurring Automation:</strong> {{ $plan['features']['recurring_automation'] ? 'Enabled' : 'Disabled' }}</li>
                            <li class="mb-2"><i class="bi bi-check text-success fs-5 align-middle"></i> <strong>Audit Trail:</strong> {{ $plan['features']['audit_trail'] ? 'Enabled' : 'Disabled' }}</li>
                        </ul>
                        <div class="border-top pt-3 text-muted small">
                            Status: <span class="badge bg-success-subtle text-success">Active & Public</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

