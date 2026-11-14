@extends('layouts.app')

@section('title', 'Settings & Profile')
@section('page-title', 'Account Settings & Integrations')

@section('content')
<div class="container-fluid px-0" style="max-width: 860px;">

    <!-- User Profile Card -->
    <div class="card-custom mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-person-gear text-primary"></i>
            <span class="fw-bold">Profile & Business Information</span>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Full Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email Address *</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Account Role</label>
                        <input type="text" class="form-control bg-light" value="{{ $user->role === 'small_business' ? 'Small Business Pro' : 'Individual Tracker' }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Business / Company Name</label>
                        <input type="text" name="business_name" class="form-control" value="{{ old('business_name', $user->business_name) }}" placeholder="e.g. Apex Digital LLC">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Primary Currency *</label>
                        <select name="currency" class="form-select">
                            <option value="USD" {{ $user->currency === 'USD' ? 'selected' : '' }}>USD ($)</option>
                            <option value="EUR" {{ $user->currency === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                            <option value="GBP" {{ $user->currency === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                            <option value="INR" {{ $user->currency === 'INR' ? 'selected' : '' }}>INR (₹)</option>
                            <option value="CAD" {{ $user->currency === 'CAD' ? 'selected' : '' }}>CAD ($)</option>
                            <option value="AUD" {{ $user->currency === 'AUD' ? 'selected' : '' }}>AUD ($)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+1 (555) 000-0000">
                    </div>
                </div>

                <!-- AI Integration Configuration Section -->
                <div class="pt-3 border-top mb-4">
                    <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-cpu-fill text-primary"></i> OpenAI-Compatible API Configuration
                    </h6>
                    <p class="text-muted small mb-3">Configure your own OpenAI-compatible endpoint (OpenAI, Gemini, Groq, Ollama, DeepSeek). If left blank, FinTrack AI uses its built-in rule engine.</p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">AI Provider</label>
                            <select name="ai_provider" class="form-select">
                                <option value="openai" {{ $user->ai_provider === 'openai' ? 'selected' : '' }}>OpenAI / Compatible API</option>
                                <option value="custom" {{ $user->ai_provider === 'custom' ? 'selected' : '' }}>Custom Self-Hosted / Local LLM</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Model Name</label>
                            <input type="text" name="ai_model" class="form-control" value="{{ old('ai_model', $user->ai_model ?: 'gpt-4o-mini') }}" placeholder="gpt-4o-mini">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">API Key</label>
                            <input type="password" name="ai_api_key" class="form-control" placeholder="sk-proj-••••••••••••••••" value="{{ $user->ai_api_key ? '••••••••••••••••' : '' }}">
                            <div class="form-text small" style="font-size: 0.725rem;">Stored securely. Leave blank to keep existing key.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">API Base URL (Optional)</label>
                            <input type="text" name="ai_base_url" class="form-control" value="{{ old('ai_base_url', $user->ai_base_url) }}" placeholder="https://api.openai.com/v1">
                            <div class="form-text small" style="font-size: 0.725rem;">Defaults to https://api.openai.com/v1</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- API & Developer Integrations (Sanctum) -->
    <div class="card-custom">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-key-fill text-warning"></i>
            <span class="fw-bold">Developer API & Laravel Sanctum Tokens</span>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small mb-3">Connect mobile apps, external billing systems, or automated accounting workflows via the FinTrack AI REST API using Bearer Token Authentication.</p>

            <div class="bg-light p-3 rounded mb-3">
                <div class="fw-bold small text-dark mb-1">API Endpoints:</div>
                <code class="d-block small text-dark mb-1">POST /api/v1/login (email, password) -> returns Bearer Token</code>
                <code class="d-block small text-dark mb-1">GET /api/v1/dashboard/summary (Header: Authorization: Bearer {token})</code>
                <code class="d-block small text-dark mb-1">GET /api/v1/transactions (Header: Authorization: Bearer {token})</code>
                <code class="d-block small text-dark">POST /api/v1/transactions (Header: Authorization: Bearer {token})</code>
            </div>

            <div class="text-muted small">
                Authentication uses Laravel Sanctum's secure personal access tokens table.
            </div>
        </div>
    </div>

</div>
@endsection

