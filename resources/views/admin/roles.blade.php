@extends('layouts.app')

@section('title', 'Admin - Roles & Permissions - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Admin Console</span>
                <h1 class="h3 fw-bold mb-0">Roles & Permissions (RBAC)</h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">Spatie RBAC authorization matrix, role hierarchies, and security policy enforcement.</p>
        </div>
        <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Overview
        </a>
    </div>

    <!-- Roles Grid -->
    <div class="row g-4 mb-4">
        @forelse($roles as $role)
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary-subtle text-primary text-uppercase">{{ $role->guard_name }}</span>
                            <span class="badge bg-light text-secondary border">{{ $role->users->count() }} Users</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">{{ ucfirst($role->name) }}</h5>
                        <p class="text-muted small mb-3">System security role granting assigned permissions.</p>

                        <div class="small fw-semibold text-muted mb-2">Granted Permissions:</div>
                        <div class="d-flex flex-wrap gap-1">
                            @forelse($role->permissions as $perm)
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">{{ $perm->name }}</span>
                            @empty
                                <span class="badge bg-success-subtle text-success" style="font-size: 0.7rem;">Full SuperAdmin Access</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-4 text-muted">No roles defined.</div>
        @endforelse
    </div>

    <!-- Permissions Reference Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold mb-0">System Permissions Inventory</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Permission Name</th>
                            <th>Guard</th>
                            <th>Description</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permissions as $perm)
                            <tr>
                                <td class="fw-bold text-primary font-monospace">{{ $perm->name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $perm->guard_name }}</span></td>
                                <td class="text-muted small">Enables user to perform {{ str_replace('.', ' ', $perm->name) }} operations.</td>
                                <td class="small text-muted">{{ $perm->created_at ? $perm->created_at->format('M d, Y') : 'System Builtin' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">No permissions seeded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

