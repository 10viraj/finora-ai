@extends('layouts.app')

@section('title', 'Admin - AI Usage & Cost - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Admin Console</span>
                <h1 class="h3 fw-bold mb-0">AI Usage, Tokens & Cost Tracking</h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">Real-time LLM token usage, estimated provider API costs, and request latency analytics.</p>
        </div>
        <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Overview
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-semibold">Total AI Messages</span>
                <h3 class="fw-bold mb-0 mt-2">{{ number_format($totalMessages) }}</h3>
                <span class="text-muted small">Lifetime queries processed</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-semibold">Total Tokens Consumed</span>
                <h3 class="fw-bold mb-0 mt-2 text-primary">{{ number_format($totalTokens) }}</h3>
                <span class="text-muted small">Prompt + completion tokens</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-semibold">Estimated Cost (USD)</span>
                <h3 class="fw-bold mb-0 mt-2 text-success">${{ number_format($totalCostUsd, 4) }}</h3>
                <span class="text-muted small">Based on model rates</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-semibold">Estimated Cost (INR)</span>
                <h3 class="fw-bold mb-0 mt-2 text-warning">₹{{ number_format($totalCostInr, 2) }}</h3>
                <span class="text-muted small">Explicit @ ₹84.50/USD</span>
            </div>
        </div>
    </div>

    <!-- Recent AI Message Invocations -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold mb-0">Recent AI Completions & Token Breakdown</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Tokens</th>
                            <th>Cost (USD)</th>
                            <th>Cost (INR)</th>
                            <th>Content Preview</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAiMessages as $msg)
                            <tr>
                                <td class="small text-muted">{{ $msg->created_at ? $msg->created_at->format('M d, H:i:s') : 'N/A' }}</td>
                                <td class="fw-semibold">{{ $msg->user ? $msg->user->name : 'Anonymous' }}</td>
                                <td>
                                    <span class="badge {{ $msg->role === 'assistant' ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ ucfirst($msg->role) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $msg->tokens_used ?? $msg->token_usage ?? 0 }}</span>
                                    <span class="text-muted small">({{ $msg->tokens_prompt ?? 0 }} in / {{ $msg->tokens_completion ?? 0 }} out)</span>
                                </td>
                                <td class="font-monospace small">${{ number_format($msg->cost_usd ?? 0, 5) }}</td>
                                <td class="font-monospace small">₹{{ number_format($msg->cost_inr ?? 0, 3) }}</td>
                                <td class="small text-muted" style="max-width: 320px;">
                                    {{ Str::limit($msg->content ?: $msg->message, 80) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No AI requests logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

