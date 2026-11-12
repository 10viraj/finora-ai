<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'FinTrack AI') }} - @yield('title', 'Smart Financial Management')</title>

    <!-- Vite Assets (Bootstrap 5, Icons, Chart.js) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <i class="bi bi-wallet2 text-primary fs-4"></i>
            <div>
                <span class="fw-bold tracking-tight">FinTrack</span><span class="text-primary fw-bold">.AI</span>
            </div>
        </a>

        <div class="sidebar-nav">
            <div class="nav-label">Main Menu</div>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i>
                <span>Transactions</span>
            </a>
            <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                <i class="bi bi-bank2"></i>
                <span>Accounts & Wallets</span>
            </a>
            <a href="{{ route('budgets.index') }}" class="nav-link {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
                <i class="bi bi-pie-chart-fill"></i>
                <span>Budgets</span>
            </a>

            <div class="nav-label mt-3">Business & Advisory</div>
            <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Invoicing</span>
            </a>
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Reports & Exports</span>
            </a>
            <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                <i class="bi bi-stars text-warning"></i>
                <span>AI Financial Advisor</span>
            </a>

            <div class="nav-label mt-3">Preferences</div>
            <a href="{{ route('profile.show') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i>
                <span>Settings & Profile</span>
            </a>
        </div>

        <!-- User Brief Footer -->
        <div class="p-3 border-top border-secondary border-opacity-25 mt-auto">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; min-width: 36px;">
                        {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="text-truncate">
                        <div class="text-white small fw-bold text-truncate">{{ Auth::user()->name ?? 'Guest' }}</div>
                        <div class="text-muted" style="font-size: 0.725rem;">
                            {{ Auth::user()->role === 'small_business' ? 'Business Pro' : 'Personal' }} ({{ Auth::user()->currency ?? 'USD' }})
                        </div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary text-light border-0" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <header class="top-navbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <h5 class="mb-0 fw-bold">@yield('page-title', 'Financial Dashboard')</h5>
                @if(Auth::user()->business_name)
                    <span class="badge bg-light text-dark border px-2 py-1">
                        <i class="bi bi-building me-1 text-primary"></i>{{ Auth::user()->business_name }}
                    </span>
                @endif
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#newTransactionModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Record Transaction</span>
                </button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm" title="Quick Reports">
                    <i class="bi bi-download"></i>
                </a>
            </div>
        </header>

        <!-- Content Body -->
        <main class="content-body">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix the following errors:</div>
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<!-- Global Quick Add Transaction Modal -->
<div class="modal fade" id="newTransactionModal" tabindex="-1" aria-labelledby="newTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('transactions.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="newTransactionModalLabel">
                        <i class="bi bi-cash-stack text-primary me-2"></i>Record Transaction
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Type Selector -->
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="type" id="modal_type_expense" value="expense" checked onchange="toggleModalType('expense')">
                            <label class="btn btn-outline-danger w-100 fw-bold py-2" for="modal_type_expense">
                                <i class="bi bi-arrow-up-right me-1"></i> Expense
                            </label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="type" id="modal_type_income" value="income" onchange="toggleModalType('income')">
                            <label class="btn btn-outline-success w-100 fw-bold py-2" for="modal_type_income">
                                <i class="bi bi-arrow-down-left me-1"></i> Income
                            </label>
                        </div>
                        <div class="col-4">
                            <input type="radio" class="btn-check" name="type" id="modal_type_transfer" value="transfer" onchange="toggleModalType('transfer')">
                            <label class="btn btn-outline-primary w-100 fw-bold py-2" for="modal_type_transfer">
                                <i class="bi bi-arrow-left-right me-1"></i> Transfer
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Amount -->
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Amount ({{ Auth::user()->currency ?? 'USD' }}) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required placeholder="0.00">
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Date *</label>
                            <input type="date" class="form-control" name="transaction_date" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <!-- Source Account -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" id="modalAccountLabel">Account *</label>
                            <select class="form-select" name="account_id" required>
                                @foreach(Auth::user()->accounts()->where('is_active', true)->get() as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (${{ number_format($acc->balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Destination Account (Transfer only) -->
                        <div class="col-md-6" id="modalDestinationWrapper" style="display: none;">
                            <label class="form-label small fw-semibold">To Account *</label>
                            <select class="form-select" name="destination_account_id">
                                <option value="">Select Destination Account</option>
                                @foreach(Auth::user()->accounts()->where('is_active', true)->get() as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->name }} (${{ number_format($acc->balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6" id="modalCategoryWrapper">
                            <label class="form-label small fw-semibold">Category *</label>
                            <select class="form-select" name="category_id">
                                @foreach(\App\Models\Category::forUser(Auth::id())->get() as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }} ({{ ucfirst($cat->type) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description *</label>
                            <input type="text" class="form-control" name="description" placeholder="e.g. AWS Cloud Hosting, Client Retainer" required>
                        </div>

                        <!-- Payee / Payer -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Payee / Payer</label>
                            <input type="text" class="form-control" name="payee_payer" placeholder="e.g. Amazon Web Services, Acme Corp">
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Payment Method</label>
                            <select class="form-select" name="payment_method">
                                <option value="bank_transfer">Bank Transfer (ACH / Wire)</option>
                                <option value="card">Credit / Debit Card</option>
                                <option value="cash">Cash / Petty Cash</option>
                                <option value="upi">UPI / Digital Wallet</option>
                                <option value="check">Check</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleModalType(type) {
    const destWrapper = document.getElementById('modalDestinationWrapper');
    const catWrapper = document.getElementById('modalCategoryWrapper');
    const accLabel = document.getElementById('modalAccountLabel');

    if (type === 'transfer') {
        destWrapper.style.display = 'block';
        catWrapper.style.display = 'none';
        accLabel.innerText = 'From Account *';
    } else {
        destWrapper.style.display = 'none';
        catWrapper.style.display = 'block';
        accLabel.innerText = 'Account *';
    }
}
</script>

@stack('scripts')
</body>
</html>
