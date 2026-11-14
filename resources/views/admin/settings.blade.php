@extends('layouts.app')

@section('title', 'Admin - System Settings - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Admin Console</span>
                <h1 class="h3 fw-bold mb-0">System & Platform Settings</h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">Runtime environment variables, AI endpoint configuration, and system information.</p>
        </div>
        <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Overview
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold mb-0">AI Engine Parameters</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Configured AI Model</label>
                        <input type="text" class="form-control" value="{{ $settings['ai_model'] }}" readonly>
                        <div class="form-text">Active LLM used for financial advisory chat and recommendations.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">AI API Base URL</label>
                        <input type="text" class="form-control" value="{{ $settings['ai_base_url'] }}" readonly>
                        <div class="form-text">Direct endpoint used by Laravel HTTP client.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Baseline Currency</label>
                        <input type="text" class="form-control" value="{{ $settings['default_currency'] }} (Indian Rupee - ₹)" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title fw-bold mb-0">Runtime & Stack Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Application Name</label>
                        <input type="text" class="form-control" value="{{ $settings['app_name'] }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Environment</label>
                        <input type="text" class="form-control" value="{{ $settings['app_env'] }}" readonly>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold text-muted">PHP Version</label>
                            <input type="text" class="form-control" value="{{ $settings['php_version'] }}" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold text-muted">Laravel Framework</label>
                            <input type="text" class="form-control" value="{{ $settings['laravel_version'] }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

