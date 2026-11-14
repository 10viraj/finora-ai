@extends('layouts.app')

@section('title', 'Admin - User Directory - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 text-uppercase fw-semibold" style="font-size: 0.7rem;">Admin Console</span>
                <h1 class="h3 fw-bold mb-0">User Directory</h1>
            </div>
            <p class="text-secondary small mb-0 mt-1">Manage tenant accounts, subscription tiers, and system access.</p>
        </div>
        <a href="{{ route('admin.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Admin Overview
        </a>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.users') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="individual" {{ request('role') === 'individual' ? 'selected' : '' }}>Individual</option>
                        <option value="small_business" {{ request('role') === 'small_business' ? 'selected' : '' }}>Small Business</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                    <a href="{{ route('admin.users') }}" class="btn btn-light border">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Plan</th>
                            <th>Currency</th>
                            <th>Accounts</th>
                            <th>Transactions</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $user->name }}</div>
                                            <div class="text-muted small">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $user->hasRole('admin') || $user->role === 'admin' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $user->subscription_plan === 'pro' ? 'bg-primary' : ($user->subscription_plan === 'enterprise' ? 'bg-success' : 'bg-light text-dark border') }} text-uppercase">
                                        {{ $user->subscription_plan ?: 'Free' }}
                                    </span>
                                </td>
                                <td class="fw-semibold">{{ $user->currency ?: 'INR' }}</td>
                                <td>{{ $user->accounts_count }}</td>
                                <td>{{ $user->transactions_count }}</td>
                                <td class="small text-muted">{{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No users matched your search criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

