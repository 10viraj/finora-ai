@extends('layouts.app')

@section('title', 'Accounts & Wallets')
@section('page-title', 'Financial Accounts & Wallets')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Action Row -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Accounts & Balances</h4>
            <p class="text-muted small mb-0">Track all checking accounts, credit cards, investments, and cash holdings in one place.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newAccountModal">
            <i class="bi bi-plus-lg me-1"></i> Add Account
        </button>
    </div>

    <!-- Total Net Balance Highlight -->
    <div class="card-custom mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
        <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="text-white-50 small text-uppercase fw-semibold tracking-wider">Total Net Liquid Worth</span>
                <h2 class="display-6 fw-bold text-white mb-0 mt-1">${{ number_format($totalBalance, 2) }}</h2>
                <div class="text-white-50 small mt-1">Across {{ $accounts->where('is_active', true)->count() }} active operational and reserve accounts</div>
            </div>
            <div>
                <button type="button" class="btn btn-light btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#newTransactionModal">
                    <i class="bi bi-arrow-left-right me-1 text-primary"></i> Transfer Between Accounts
                </button>
            </div>
        </div>
    </div>

    <!-- Accounts Cards Grid -->
    <div class="row g-3">
        @foreach($accounts as $acc)
            @php
                $typeIcons = [
                    'checking' => 'bi-bank',
                    'business_checking' => 'bi-building-gear',
                    'savings' => 'bi-piggy-bank',
                    'credit_card' => 'bi-credit-card-2-front',
                    'cash' => 'bi-cash',
                    'investment' => 'bi-graph-up',
                ];
                $icon = $typeIcons[$acc->type] ?? 'bi-wallet';
            @endphp
            <div class="col-lg-4 col-md-6">
                <div class="card-custom h-100 {{ !$acc->is_active ? 'opacity-75' : '' }}">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 p-3 text-white" style="background-color: {{ $acc->color }}; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi {{ $icon }} fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0">{{ $acc->name }}</h5>
                                    <span class="text-muted small">
                                        {{ ucwords(str_replace('_', ' ', $acc->type)) }}
                                        @if($acc->account_number_last4) •••• {{ $acc->account_number_last4 }} @endif
                                    </span>
                                </div>
                            </div>
                            <span class="badge {{ $acc->is_active ? 'bg-success' : 'bg-secondary' }} px-2 py-1">
                                {{ $acc->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="mt-4 mb-2">
                            <div class="text-muted small">Current Balance</div>
                            <h3 class="fw-bold {{ $acc->balance < 0 ? 'text-danger' : 'text-dark' }} mb-0">
                                ${{ number_format($acc->balance, 2) }}
                            </h3>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-3 mt-3 border-top text-muted small">
                            <span><i class="bi bi-clock-history me-1"></i> {{ $acc->transactions_count }} Transactions</span>
                            <form action="{{ route('accounts.toggle', $acc) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none {{ $acc->is_active ? 'text-secondary' : 'text-primary' }}">
                                    {{ $acc->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>

<!-- Modal: New Account -->
<div class="modal fade" id="newAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('accounts.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-bank text-primary me-2"></i>Add New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Account Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Chase Business Checking" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Account Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="checking">Checking Account</option>
                                <option value="business_checking">Business Checking</option>
                                <option value="savings">Savings / High-Yield</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="cash">Cash / Petty Cash</option>
                                <option value="investment">Investment / Brokerage</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last 4 Digits</label>
                            <input type="text" name="account_number_last4" maxlength="4" class="form-control" placeholder="1234">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Initial Balance ($) *</label>
                            <input type="number" step="0.01" name="initial_balance" class="form-control" value="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Color Tag</label>
                            <input type="color" name="color" class="form-control form-control-color w-100" value="#2563eb">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description / Notes</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="e.g. Primary operational account for vendor payouts"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

