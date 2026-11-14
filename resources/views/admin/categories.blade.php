@extends('layouts.app')

@section('title', 'Admin - Categories - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Admin Console</span>
                <h1 class="h3 fw-bold mb-0">System Category Catalog</h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">Manage global default categories available to all registered tenants.</p>
        </div>
        <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Overview
        </a>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">Global System Categories</span>
                        <h3 class="fw-bold mb-0 mt-1">{{ $systemCategories->count() }}</h3>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-3">
                        <i class="bi bi-tags-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small text-uppercase fw-semibold">User Custom Categories Created</span>
                        <h3 class="fw-bold mb-0 mt-1">{{ number_format($userCategoriesCount) }}</h3>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-3">
                        <i class="bi bi-folder-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Categories List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold mb-0">Standard Default Categories</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Icon</th>
                            <th>Color</th>
                            <th>Global Default</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($systemCategories as $cat)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; background-color: {{ $cat->color ?: '#6366f1' }};">
                                            <i class="bi {{ $cat->icon ?: 'bi-tag' }}"></i>
                                        </div>
                                        <span class="fw-bold">{{ $cat->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $cat->type === 'income' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                                        {{ $cat->type }}
                                    </span>
                                </td>
                                <td class="font-monospace small">{{ $cat->icon ?: 'bi-tag' }}</td>
                                <td>
                                    <span class="d-inline-block rounded-circle border me-1" style="width: 14px; height: 14px; background-color: {{ $cat->color }}; vertical-align: middle;"></span>
                                    <span class="small font-monospace">{{ $cat->color }}</span>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary">Yes (All Tenants)</span></td>
                                <td><span class="badge bg-success">Active</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No default system categories loaded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

