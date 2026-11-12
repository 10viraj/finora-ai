@extends('layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Financial Transactions')

@section('content')
<div class="container-fluid px-0">

    <!-- Filters & Search Toolbar -->
    <div class="card-custom mb-4">
        <div class="card-body p-3">
            <form action="{{ route('transactions.index') }}" method="GET" id="filterForm">
                <!-- Row 1: Primary Search & Quick Filters -->
                <div class="row g-2 align-items-center mb-2">
                    <!-- Search Input -->
                    <div class="col-lg-4 col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search payee, memo, reference #..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="col-lg-2 col-md-3 col-6">
                        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="income" {{ request('type') === 'income' ? 'selected' : '' }}>Income (+)</option>
                            <option value="expense" {{ request('type') === 'expense' ? 'selected' : '' }}>Expense (-)</option>
                            <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                        </select>
                    </div>

                    <!-- Account Filter -->
                    <div class="col-lg-2 col-md-3 col-6">
                        <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Accounts</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category & Subcategory Filter -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            @foreach($parentCategories as $parent)
                                <optgroup label="{{ $parent->name }}">
                                    <option value="{{ $parent->id }}" {{ request('category_id') == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->name }} (All)
                                    </option>
                                    @foreach($parent->children as $child)
                                        <option value="{{ $child->id }}" {{ request('category_id') == $child->id ? 'selected' : '' }}>
                                            ↳ {{ $child->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Recurring Toggle Filter -->
                    <div class="col-lg-2 col-md-4 col-6">
                        <select name="is_recurring" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Frequencies</option>
                            <option value="1" {{ request('is_recurring') === '1' ? 'selected' : '' }}>🔄 Recurring Only</option>
                            <option value="0" {{ request('is_recurring') === '0' ? 'selected' : '' }}>One-Time Only</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Secondary Advanced Filters (collapsible / inline) -->
                <div class="row g-2 align-items-center">
                    <!-- Payment Method -->
                    <div class="col-lg-3 col-md-4 col-6">
                        <select name="payment_method" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Payment Methods</option>
                            <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer (ACH/Wire)</option>
                            <option value="credit_card" {{ request('payment_method') === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                            <option value="debit_card" {{ request('payment_method') === 'debit_card' ? 'selected' : '' }}>Debit Card</option>
                            <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="upi" {{ request('payment_method') === 'upi' ? 'selected' : '' }}>UPI / Mobile Pay</option>
                            <option value="paypal" {{ request('payment_method') === 'paypal' ? 'selected' : '' }}>PayPal</option>
                            <option value="stripe" {{ request('payment_method') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                            <option value="check" {{ request('payment_method') === 'check' ? 'selected' : '' }}>Check</option>
                            <option value="other" {{ request('payment_method') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="col-lg-2 col-md-3 col-6">
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" title="Date From" placeholder="From Date">
                    </div>
                    <div class="col-lg-2 col-md-3 col-6">
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" title="Date To" placeholder="To Date">
                    </div>

                    <!-- Amount Range -->
                    <div class="col-lg-3 col-md-4 col-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" name="min_amount" class="form-control" placeholder="Min" value="{{ request('min_amount') }}">
                            <span class="input-group-text">to</span>
                            <input type="number" step="0.01" name="max_amount" class="form-control" placeholder="Max" value="{{ request('max_amount') }}">
                        </div>
                    </div>

                    <!-- Submit / Reset -->
                    <div class="col-lg-2 col-md-4 col-12 d-flex gap-1 justify-content-end">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="bi bi-funnel me-1"></i> Apply
                        </button>
                        @if(request()->anyFilled(['search', 'type', 'account_id', 'category_id', 'payment_method', 'is_recurring', 'date_from', 'date_to', 'min_amount', 'max_amount']))
                            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions List Card -->
    <div class="card-custom">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left-right text-primary"></i>
                <span class="fw-bold">Itemized Transactions</span>
                <span class="badge bg-light text-muted border">{{ $transactions->total() }} recorded</span>
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
                            <th>Description & Payee</th>
                            <th>Type</th>
                            <th>Category / Subcategory</th>
                            <th>Account</th>
                            <th>Method</th>
                            <th class="text-center">Receipt</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <!-- Date -->
                                <td class="ps-3 text-nowrap small text-secondary">
                                    {{ $tx->transaction_date ? $tx->transaction_date->format('M d, Y') : '-' }}
                                    @if($tx->is_recurring)
                                        <div class="badge bg-light text-primary border" style="font-size: 0.65rem;" title="Recurring {{ ucfirst($tx->recurrence_interval) }}">
                                            <i class="bi bi-arrow-repeat me-1"></i>{{ ucfirst($tx->recurrence_interval ?: 'monthly') }}
                                        </div>
                                    @endif
                                </td>

                                <!-- Description & Payee -->
                                <td>
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 240px;">
                                        {{ $tx->description }}
                                    </div>
                                    <div class="text-muted small d-flex align-items-center gap-2">
                                        @if($tx->payee_payer)
                                            <span><i class="bi bi-person me-1"></i>{{ $tx->payee_payer }}</span>
                                        @endif
                                        @if($tx->reference_number)
                                            <span class="badge bg-light text-secondary border" style="font-size: 0.65rem;">#{{ $tx->reference_number }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Type -->
                                <td>
                                    <span class="badge rounded-pill {{ $tx->type === 'income' ? 'badge-income' : ($tx->type === 'expense' ? 'badge-expense' : 'badge-transfer') }} px-2 py-1">
                                        {{ ucfirst($tx->type) }}
                                    </span>
                                </td>

                                <!-- Category & Subcategory -->
                                <td>
                                    @if($tx->category)
                                        <div>
                                            @if($tx->category->parent)
                                                <span class="text-muted small d-block" style="font-size: 0.725rem;">
                                                    {{ $tx->category->parent->name }}
                                                </span>
                                            @endif
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi {{ $tx->category->icon ?? 'bi-tag' }} me-1" style="color: {{ $tx->category->color }}"></i>
                                                {{ $tx->category->name }}
                                            </span>
                                        </div>
                                    @elseif($tx->destinationAccount)
                                        <span class="badge bg-light text-primary border">
                                            <i class="bi bi-arrow-right me-1"></i>{{ $tx->destinationAccount->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                <!-- Account -->
                                <td class="small text-muted text-nowrap">
                                    <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: {{ $tx->account->color ?? '#2563eb' }};"></span>
                                    {{ $tx->account->name ?? 'Default' }}
                                </td>

                                <!-- Payment Method -->
                                <td class="small text-muted text-nowrap">
                                    {{ $tx->formatted_payment_method }}
                                </td>

                                <!-- Receipt Attachment -->
                                <td class="text-center text-nowrap">
                                    @if($tx->hasReceipt())
                                        <a href="{{ route('transactions.receipt', $tx) }}" target="_blank" class="btn btn-sm btn-outline-primary border-0 p-1" title="View / Download Receipt Attachment">
                                            <i class="bi bi-file-earmark-arrow-down fs-5"></i>
                                        </a>
                                    @else
                                        <span class="text-muted" style="font-size: 0.75rem;">—</span>
                                    @endif
                                </td>

                                <!-- Amount -->
                                <td class="text-end text-nowrap">
                                    @if($tx->type === 'income')
                                        <span class="amount-income fs-6">+${{ number_format($tx->amount, 2) }}</span>
                                    @elseif($tx->type === 'expense')
                                        <span class="amount-expense fs-6">-${{ number_format($tx->amount, 2) }}</span>
                                    @else
                                        <span class="text-primary fw-semibold fs-6">${{ number_format($tx->amount, 2) }}</span>
                                    @endif
                                    @if($tx->fee > 0)
                                        <div class="text-muted" style="font-size: 0.675rem;">Fee: ${{ number_format($tx->fee, 2) }}</div>
                                    @endif
                                </td>

                                <!-- Actions: Edit & Delete -->
                                <td class="text-center pe-3 text-nowrap">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary border-0 p-1" title="Edit Transaction" onclick="openEditModal({{ json_encode($tx) }})">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form action="{{ route('transactions.destroy', $tx) }}" method="POST" onsubmit="return confirm('Delete this transaction? The account balance will be automatically adjusted.');" class="m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                                    No transactions match your search filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($transactions->hasPages())
            <div class="card-footer bg-white border-0 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="small text-muted">
                    Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions
                </div>
                <div>
                    {{ $transactions->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

</div>

<!-- Modal: Edit Transaction -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="editTransactionForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square text-primary me-2"></i>Edit Transaction
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Type Selector -->
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="type" id="edit_type_expense" value="expense">
                            <label class="btn btn-outline-danger w-100 fw-bold py-2" for="edit_type_expense">Expense</label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="type" id="edit_type_income" value="income">
                            <label class="btn btn-outline-success w-100 fw-bold py-2" for="edit_type_income">Income</label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="type" id="edit_type_transfer" value="transfer">
                            <label class="btn btn-outline-primary w-100 fw-bold py-2" for="edit_type_transfer">Transfer</label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Amount *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="edit_amount" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Fee / Surcharge</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="fee" id="edit_fee">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Date *</label>
                            <input type="date" class="form-control" name="transaction_date" id="edit_date" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Account *</label>
                            <select class="form-select" name="account_id" id="edit_account_id" required>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (${{ number_format($acc->balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6" id="editDestinationWrapper" style="display: none;">
                            <label class="form-label small fw-semibold">To Account *</label>
                            <select class="form-select" name="destination_account_id" id="edit_destination_account_id">
                                <option value="">Select Destination Account</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (${{ number_format($acc->balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6" id="editCategoryWrapper">
                            <label class="form-label small fw-semibold">Category / Subcategory</label>
                            <select class="form-select" name="category_id" id="edit_category_id">
                                @foreach($parentCategories as $parent)
                                    <optgroup label="{{ $parent->name }} ({{ ucfirst($parent->type) }})">
                                        <option value="{{ $parent->id }}">{{ $parent->name }} (General)</option>
                                        @foreach($parent->children as $child)
                                            <option value="{{ $child->id }}">↳ {{ $child->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Description *</label>
                            <input type="text" class="form-control" name="description" id="edit_description" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Payee / Payer</label>
                            <input type="text" class="form-control" name="payee_payer" id="edit_payee_payer">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Payment Method *</label>
                            <select class="form-select" name="payment_method" id="edit_payment_method" required>
                                <option value="bank_transfer">Bank Transfer (ACH/Wire)</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="cash">Cash / Petty Cash</option>
                                <option value="upi">UPI / Mobile Pay</option>
                                <option value="paypal">PayPal</option>
                                <option value="stripe">Stripe</option>
                                <option value="check">Check</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Reference #</label>
                            <input type="text" class="form-control" name="reference_number" id="edit_reference_number">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Replace Receipt (Optional)</label>
                            <input type="file" class="form-control" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        </div>

                        <div class="col-md-6 d-flex align-items-center">
                            <div class="w-100 p-2 border rounded bg-light">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="edit_is_recurring">
                                    <label class="form-check-label small fw-semibold text-dark" for="edit_is_recurring">
                                        Recurring Transaction
                                    </label>
                                </div>
                                <select class="form-select form-select-sm mt-1" name="recurrence_interval" id="edit_recurrence_interval">
                                    <option value="monthly">Monthly</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="daily">Daily</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notes</label>
                            <textarea class="form-control" name="notes" id="edit_notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Update Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(tx) {
    const form = document.getElementById('editTransactionForm');
    form.action = `/transactions/${tx.id}`;

    // Type radio
    if (tx.type === 'expense') document.getElementById('edit_type_expense').checked = true;
    else if (tx.type === 'income') document.getElementById('edit_type_income').checked = true;
    else if (tx.type === 'transfer') document.getElementById('edit_type_transfer').checked = true;

    document.getElementById('edit_amount').value = tx.amount;
    document.getElementById('edit_fee').value = tx.fee || 0;
    document.getElementById('edit_date').value = tx.transaction_date ? tx.transaction_date.substring(0, 10) : '';
    document.getElementById('edit_account_id').value = tx.account_id;
    document.getElementById('edit_description').value = tx.description;
    document.getElementById('edit_payee_payer').value = tx.payee_payer || '';
    document.getElementById('edit_payment_method').value = tx.payment_method || 'bank_transfer';
    document.getElementById('edit_reference_number').value = tx.reference_number || '';
    document.getElementById('edit_notes').value = tx.notes || '';

    // Category / Destination
    if (tx.type === 'transfer') {
        document.getElementById('editDestinationWrapper').style.display = 'block';
        document.getElementById('editCategoryWrapper').style.display = 'none';
        document.getElementById('edit_destination_account_id').value = tx.destination_account_id || '';
    } else {
        document.getElementById('editDestinationWrapper').style.display = 'none';
        document.getElementById('editCategoryWrapper').style.display = 'block';
        document.getElementById('edit_category_id').value = tx.category_id || '';
    }

    // Recurring
    document.getElementById('edit_is_recurring').checked = Boolean(tx.is_recurring);
    document.getElementById('edit_recurrence_interval').value = tx.recurrence_interval || 'monthly';

    const modal = new bootstrap.Modal(document.getElementById('editTransactionModal'));
    modal.show();
}
</script>
@endsection
