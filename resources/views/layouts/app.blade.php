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
    <!-- Mobile Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="appSidebar">
        @if(request()->is('admin*'))
            <!-- Admin Console Brand -->
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom border-secondary border-opacity-25">
                <a href="{{ route('admin.index') }}" class="brand p-0 border-0">
                    <i class="bi bi-shield-check text-danger fs-4"></i>
                    <div>
                        <span class="fw-bold tracking-tight">Finora</span><span class="text-danger fw-bold">.Admin</span>
                    </div>
                </a>
                <button type="button" class="btn btn-sm btn-link text-white-50 text-decoration-none d-lg-none p-1 sidebar-close-trigger" aria-label="Close sidebar">
                    <i class="bi bi-x-lg fs-5"></i>
                </button>
            </div>

            <div class="sidebar-nav">
                <div class="nav-label">Admin Console</div>
                <a href="{{ route('admin.index') }}" class="nav-link {{ request()->routeIs('admin.index') || request()->routeIs('admin.overview') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>
                    <span>Overview</span>
                </a>
                <a href="{{ route('admin.users') }}" class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Users</span>
                </a>
                <a href="{{ route('admin.roles') }}" class="nav-link {{ request()->routeIs('admin.roles') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span>Roles &amp; Permissions</span>
                </a>
                <a href="{{ route('admin.categories') }}" class="nav-link {{ request()->routeIs('admin.categories') ? 'active' : '' }}">
                    <i class="bi bi-tags-fill"></i>
                    <span>Categories</span>
                </a>
                <a href="{{ route('admin.subscriptions') }}" class="nav-link {{ request()->routeIs('admin.subscriptions') ? 'active' : '' }}">
                    <i class="bi bi-credit-card-fill"></i>
                    <span>Subscription Plans</span>
                </a>
                <a href="{{ route('admin.ai-usage') }}" class="nav-link {{ request()->routeIs('admin.ai-usage') ? 'active' : '' }}">
                    <i class="bi bi-cpu-fill"></i>
                    <span>AI Usage</span>
                </a>
                <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    <i class="bi bi-sliders"></i>
                    <span>System Settings</span>
                </a>
                <a href="{{ route('admin.audit-logs') }}" class="nav-link {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i>
                    <span>Audit Logs</span>
                </a>

                <div class="nav-label mt-4">Exit Console</div>
                <a href="{{ route('dashboard') }}" class="nav-link text-warning">
                    <i class="bi bi-arrow-left-square-fill"></i>
                    <span>Back to App Dashboard</span>
                </a>
            </div>
        @else
            <!-- Standard SaaS Brand -->
            <div class="d-flex align-items-center justify-content-between p-3 border-bottom border-secondary border-opacity-25">
                <a href="{{ route('dashboard') }}" class="brand p-0 border-0">
                    <i class="bi bi-wallet2 text-primary fs-4"></i>
                    <div>
                        <span class="fw-bold tracking-tight">Finora</span><span class="text-primary fw-bold">.AI</span>
                    </div>
                </a>
                <button type="button" class="btn btn-sm btn-link text-white-50 text-decoration-none d-lg-none p-1 sidebar-close-trigger" aria-label="Close sidebar">
                    <i class="bi bi-x-lg fs-5"></i>
                </button>
            </div>

            <div class="sidebar-nav">
                <div class="nav-label">Main Menu</div>
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.index') && !request()->filled('type') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transactions</span>
                </a>
                <a href="{{ route('transactions.income') }}" class="nav-link {{ request()->get('type') === 'income' ? 'active' : '' }}">
                    <i class="bi bi-arrow-down-left-circle-fill text-success"></i>
                    <span>Income</span>
                </a>
                <a href="{{ route('transactions.expenses') }}" class="nav-link {{ request()->get('type') === 'expense' ? 'active' : '' }}">
                    <i class="bi bi-arrow-up-right-circle-fill text-danger"></i>
                    <span>Expenses</span>
                </a>
                <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <i class="bi bi-tags-fill"></i>
                    <span>Categories</span>
                </a>
                <a href="{{ route('accounts.index') }}" class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <i class="bi bi-bank2"></i>
                    <span>Accounts</span>
                </a>
                <a href="{{ route('budgets.index') }}" class="nav-link {{ request()->routeIs('budgets.*') ? 'active' : '' }}">
                    <i class="bi bi-pie-chart-fill"></i>
                    <span>Budgets</span>
                </a>
                <a href="{{ route('goals.index') }}" class="nav-link {{ request()->routeIs('goals.*') ? 'active' : '' }}">
                    <i class="bi bi-bullseye"></i>
                    <span>Financial Goals</span>
                </a>

                <div class="nav-label mt-3">Intelligence & Reports</div>
                <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                    <i class="bi bi-stars text-warning"></i>
                    <span>AI Assistant</span>
                </a>
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Reports</span>
                </a>
                @php
                    $unreadAlertsCount = Auth::check() ? Auth::user()->notifications()->whereNull('read_at')->count() : 0;
                @endphp
                <a href="{{ route('notifications.index') }}" class="nav-link d-flex align-items-center justify-content-between {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bell-fill"></i>
                        <span>Notifications</span>
                    </div>
                    @if($unreadAlertsCount > 0)
                        <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">{{ $unreadAlertsCount }}</span>
                    @endif
                </a>

                <div class="nav-label mt-3">Preferences</div>
                <a href="{{ route('profile.show') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <i class="bi bi-gear-fill"></i>
                    <span>Settings</span>
                </a>

                @if(Auth::check() && (Auth::user()->hasRole('admin') || Auth::user()->role === 'admin'))
                    <div class="nav-label mt-3">Administration</div>
                    <a href="{{ route('admin.index') }}" class="nav-link text-danger-emphasis">
                        <i class="bi bi-shield-lock-fill text-danger"></i>
                        <span>Admin Console</span>
                    </a>
                @endif
            </div>
        @endif

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
            <div class="d-flex align-items-center gap-2 gap-md-3">
                <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none py-1 px-2" id="sidebarToggleBtn" aria-label="Toggle navigation">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h5 class="mb-0 fw-bold fs-6 fs-md-5 text-truncate" style="max-width: 190px;">@yield('page-title', 'Financial Dashboard')</h5>
                @if(Auth::user()->business_name)
                    <span class="badge bg-light text-dark border px-2 py-1 d-none d-sm-inline-flex">
                        <i class="bi bi-building me-1 text-primary"></i>{{ Auth::user()->business_name }}
                    </span>
                @endif
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm px-2 px-sm-3" data-bs-toggle="modal" data-bs-target="#newTransactionModal">
                    <i class="bi bi-plus-lg"></i>
                    <span class="d-none d-sm-inline">Record Transaction</span>
                    <span class="d-inline d-sm-none">Record</span>
                </button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm" title="Quick Reports">
                    <i class="bi bi-download"></i>
                </a>
            </div>
        </header>

        <!-- Content Body -->
        <main class="content-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('budget_alert'))
                <div class="alert alert-{{ session('budget_alert')['severity'] === 'danger' ? 'danger' : 'warning' }} alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div class="flex-grow-1">
                        <strong>Budget Notice:</strong> {{ session('budget_alert')['message'] }}
                        <a href="{{ route('budgets.index') }}" class="alert-link ms-2">Manage Budgets &rarr;</a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please check errors:</div>
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
    <!-- /Main Content -->

    <!-- Mobile Bottom Navigation Bar (Visible on mobile/tablet screens) -->
    <nav class="mobile-bottom-nav d-lg-none" aria-label="Mobile Navigation">
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('transactions.index') }}" class="nav-item {{ request()->routeIs('transactions.*') && !request()->filled('type') ? 'active' : '' }}">
            <i class="bi bi-arrow-left-right"></i>
            <span>Activity</span>
        </a>

        <!-- Center Floating Action Button (FAB) -->
        <button type="button" class="fab-item" data-bs-toggle="modal" data-bs-target="#newTransactionModal" aria-label="New Transaction">
            <i class="bi bi-plus-lg"></i>
        </button>

        <a href="{{ route('ai.index') }}" class="nav-item {{ request()->routeIs('ai.*') ? 'active' : '' }}">
            <i class="bi bi-stars text-warning"></i>
            <span>AI Copilot</span>
        </a>
        <button type="button" class="nav-item" id="sidebarBottomToggleBtn" aria-label="Open Menu">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            <span>Menu</span>
        </button>
    </nav>
</div>

<!-- Global Quick Add Transaction Modal -->
<div class="modal fade" id="newTransactionModal" tabindex="-1" aria-labelledby="newTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data">
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
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Amount ({{ Auth::user()->currency ?? 'USD' }}) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required placeholder="0.00">
                            </div>
                        </div>

                        <!-- Processing Fee -->
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Fee / Surcharge</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="fee" value="0.00">
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-4">
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

                        <!-- Category & Subcategories -->
                        <div class="col-md-6" id="modalCategoryWrapper">
                            <label class="form-label small fw-semibold">Category & Subcategory *</label>
                            <select class="form-select" name="category_id">
                                @php
                                    $parents = \App\Models\Category::forUser(Auth::id())->whereNull('parent_id')->with('children')->get();
                                @endphp
                                @foreach($parents as $parent)
                                    <optgroup label="{{ $parent->name }} ({{ ucfirst($parent->type) }})">
                                        <option value="{{ $parent->id }}">{{ $parent->name }} (General)</option>
                                        @foreach($parent->children as $child)
                                            <option value="{{ $child->id }}">↳ {{ $child->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="col-md-6">
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
                            <label class="form-label small fw-semibold">Payment Method *</label>
                            <select class="form-select" name="payment_method" required>
                                <option value="bank_transfer">Bank Transfer (ACH / Wire)</option>
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

                        <!-- Reference Number -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Reference / Invoice #</label>
                            <input type="text" class="form-control" name="reference_number" placeholder="e.g. INV-2026-001, ACH-9921">
                        </div>

                        <!-- Attachment / Receipt Upload -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">
                                <i class="bi bi-paperclip me-1"></i> Receipt / Invoice Attachment
                            </label>
                            <input type="file" class="form-control" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <div class="form-text small" style="font-size: 0.725rem;">PDF, PNG, JPG up to 5MB</div>
                        </div>

                        <!-- Recurring Transaction Controls -->
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="w-100 p-2 border rounded bg-light">
                                <div class="form-check form-switch mb-1">
                                    <input class="form-check-input" type="checkbox" name="is_recurring" value="1" id="is_recurring_check" onchange="toggleRecurringModal(this.checked)">
                                    <label class="form-check-label small fw-semibold text-dark" for="is_recurring_check">
                                        <i class="bi bi-arrow-repeat me-1 text-primary"></i> Mark as Recurring Transaction
                                    </label>
                                </div>
                                <div id="recurringIntervalWrapper" style="display: none;" class="mt-2">
                                    <label class="form-label small fw-semibold">Recurrence Frequency</label>
                                    <select class="form-select form-select-sm" name="recurrence_interval">
                                        <option value="monthly" selected>Monthly (e.g. rent, software)</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="yearly">Yearly</option>
                                        <option value="daily">Daily</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notes / Memo</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes for accounting or tax reconciliation"></textarea>
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

function toggleRecurringModal(checked) {
    document.getElementById('recurringIntervalWrapper').style.display = checked ? 'block' : 'none';
}

// Mobile Sidebar Drawer & Backdrop Handlers
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const bottomToggleBtn = document.getElementById('sidebarBottomToggleBtn');
    const closeButtons = document.querySelectorAll('.sidebar-close-trigger');

    function openSidebar() {
        if (sidebar && backdrop) {
            sidebar.classList.add('show');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSidebar() {
        if (sidebar && backdrop) {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (bottomToggleBtn) bottomToggleBtn.addEventListener('click', openSidebar);
    closeButtons.forEach(btn => btn.addEventListener('click', closeSidebar));
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // Auto-close on link navigation on mobile
    if (sidebar) {
        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    closeSidebar();
                }
            });
        });
    }
});
</script>

@stack('scripts')
</body>
</html>
