@extends('layouts.app')

@section('title', 'Admin Overview - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Admin Console</span>
                <h1 class="h3 fw-bold mb-0">System Overview & Governance</h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">Platform-wide analytics, RBAC permissions, AI usage tracking, and multi-tenant health.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-box-arrow-left me-1"></i> Exit to App
            </a>
            <a href="{{ route('admin.audit-logs') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-journal-text me-1"></i> Full Audit Trail
            </a>
        </div>
    </div>

    <!-- Stat KPI Grid -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Total Users</span>
                        <h3 class="fw-bold mb-0 mt-2">{{ number_format($totalUsers ?? 0) }}</h3>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-3">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top small text-muted">
                    <a href="{{ route('admin.users') }}" class="text-primary text-decoration-none fw-semibold">View all accounts &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Platform Ledger Volume</span>
                        <h3 class="fw-bold mb-0 mt-2">₹{{ number_format($totalVolume ?? 0, 2) }}</h3>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-3">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top small text-muted">
                    <span>{{ number_format($totalTransactions ?? 0) }} completed transactions</span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Today's AI Queries</span>
                        <h3 class="fw-bold mb-0 mt-2">{{ number_format($todayAiQueries ?? 0) }}</h3>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-3">
                        <i class="bi bi-robot fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top small text-muted">
                    <a href="{{ route('admin.ai-usage') }}" class="text-warning text-decoration-none fw-semibold">Analyze AI token costs &rarr;</a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Security Roles</span>
                        <h3 class="fw-bold mb-0 mt-2">{{ $roles->count() ?? 0 }}</h3>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-3">
                        <i class="bi bi-shield-lock-fill fs-4"></i>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top small text-muted">
                    <a href="{{ route('admin.roles') }}" class="text-info text-decoration-none fw-semibold">Manage RBAC policies &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Shortcuts -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">Admin Management Modules</h6>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <a href="{{ route('admin.users') }}" class="btn btn-light w-100 text-start p-3 border">
                                <i class="bi bi-people-fill text-primary me-2"></i>
                                <span class="fw-semibold">User Directory</span>
                                <div class="text-muted small mt-1">Manage accounts and permissions</div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.roles') }}" class="btn btn-light w-100 text-start p-3 border">
                                <i class="bi bi-shield-check text-success me-2"></i>
                                <span class="fw-semibold">Roles & Permissions</span>
                                <div class="text-muted small mt-1">Spatie RBAC policy assignments</div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.subscriptions') }}" class="btn btn-light w-100 text-start p-3 border">
                                <i class="bi bi-credit-card-fill text-info me-2"></i>
                                <span class="fw-semibold">Subscription Plans</span>
                                <div class="text-muted small mt-1">Free, Pro, and Enterprise tiers</div>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.ai-usage') }}" class="btn btn-light w-100 text-start p-3 border">
                                <i class="bi bi-cpu-fill text-warning me-2"></i>
                                <span class="fw-semibold">AI Usage & Cost</span>
                                <div class="text-muted small mt-1">API tokens and cost metrics</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Audit Logs Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">Recent System Activity Audit Trail</h5>
            <a href="{{ route('admin.audit-logs') }}" class="btn btn-outline-primary btn-sm">View All Logs</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity Type</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAuditLogs ?? [] as $log)
                            <tr>
                                <td class="small text-muted">{{ $log->created_at ? $log->created_at->format('M d, Y H:i:s') : 'N/A' }}</td>
                                <td class="fw-semibold">{{ $log->user ? $log->user->name : 'System / Background' }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $log->action }}</span>
                                </td>
                                <td class="small">{{ $log->entity_type ? class_basename($log->entity_type) : '-' }} #{{ $log->entity_id ?? '-' }}</td>
                                <td class="small text-muted font-monospace">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                                <td>
                                    @if(!empty($log->new_values))
                                        <span class="badge bg-light text-dark border" title="{{ json_encode($log->new_values) }}">Data Captured</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No audit activity logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
