@extends('layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Financial Transactions')

@section('content')
<div class="container-fluid px-0">

    <!-- Filters & Search Toolbar -->
    <div class="card-custom mb-4">
        <div class="card-body py-3">
            <form action="{{ route('transactions.index') }}" method="GET" class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search payee, notes..." value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Type -->
                <div class="col-lg-2 col-md-3 col-6">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income</option>
                        <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                        <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    </select>
                </div>

                <!-- Account -->
                <div class="col-lg-2 col-md-3 col-6">
                    <select name="account_id" class="form-select form-select-sm">
                        <option value="">All Accounts</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Category -->
                <div class="col-lg-2 col-md-4 col-6">
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date From -->
                <div class="col-lg-1 col-md-4 col-3">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" title="Date From">
                </div>

                <!-- Actions -->
                <div class="col-lg-2 col-md-4 col-3 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    @if(request()->anyFilled(['search', 'type', 'account_id', 'category_id', 'date_from', 'date_to']))
                        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions List Card -->
    <div class="card-custom">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-list-ul text-primary"></i>
                <span class="fw-bold">Transaction History</span>
                <span class="badge bg-light text-muted border">{{ $transactions->total() }} records</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reports.excel', request()->all()) }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                </a>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTransactionModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Transaction
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Account</th>
                            <th>Method</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <td class="ps-3 text-nowrap small text-secondary">
                                    {{ $tx->transaction_date ? $tx->transaction_date->format('M d, Y') : '-' }}
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $tx->description }}</div>
                                    @if($tx->payee_payer)
                                        <div class="text-muted small"><i class="bi bi-person me-1"></i>{{ $tx->payee_payer }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge rounded-pill {{ $tx->type === 'income' ? 'badge-income' : ($tx->type === 'expense' ? 'badge-expense' : 'badge-transfer') }} px-2 py-1">
                                        {{ ucfirst($tx->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($tx->category)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi {{ $tx->category->icon ?? 'bi-tag' }} me-1" style="color: {{ $tx->category->color }}"></i>
                                            {{ $tx->category->name }}
                                        </span>
                                    @elseif($tx->destinationAccount)
                                        <span class="badge bg-light text-primary border">
                                            <i class="bi bi-arrow-right me-1"></i>{{ $tx->destinationAccount->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="small text-muted text-nowrap">
                                    <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: {{ $tx->account->color ?? '#2563eb' }};"></span>
                                    {{ $tx->account->name ?? 'Default' }}
                                </td>
                                <td class="small text-muted text-nowrap">
                                    {{ ucwords(str_replace('_', ' ', $tx->payment_method)) }}
                                </td>
                                <td class="text-end text-nowrap">
                                    @if($tx->type === 'income')
                                        <span class="amount-income fs-6">+${{ number_format($tx->amount, 2) }}</span>
                                    @elseif($tx->type === 'expense')
                                        <span class="amount-expense fs-6">-${{ number_format($tx->amount, 2) }}</span>
                                    @else
                                        <span class="text-primary fw-semibold fs-6">${{ number_format($tx->amount, 2) }}</span>
                                    @endif
                                </td>
                                <td class="text-center pe-3">
                                    <form action="{{ route('transactions.destroy', $tx) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 py-0 px-2" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                                    No transactions match your search filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $transactions->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>
@endsection
