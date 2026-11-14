<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AiAdvisorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Public Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Root redirect
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Module 1: Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Module 2: Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::post('/transactions/scan-receipt', [TransactionController::class, 'scanReceipt'])->name('transactions.scan-receipt');
    Route::post('/transactions/import-csv', [TransactionController::class, 'importCsv'])->name('transactions.import-csv');
    Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'downloadReceipt'])->name('transactions.receipt');

    // Accounts
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::patch('/accounts/{account}/toggle', [AccountController::class, 'toggleStatus'])->name('accounts.toggle');

    // Budgets
    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::get('/budgets/export/csv', [BudgetController::class, 'exportComparison'])->name('budgets.export');
    Route::get('/budgets/{budget}/edit', [BudgetController::class, 'edit'])->name('budgets.edit');
    Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->name('invoices.status');

    // Reports & Exports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/pdf', [ReportController::class, 'downloadPdf'])->name('reports.pdf');
    Route::get('/reports/excel', [ReportController::class, 'exportExcel'])->name('reports.excel');

    // AI Financial Advisor (Module 4)
    Route::get('/ai-advisor', [AiAdvisorController::class, 'index'])->name('ai.index');
    Route::post('/ai-advisor/generate', [AiAdvisorController::class, 'generate'])->name('ai.generate')->middleware('throttle:ai-assistant');
    Route::post('/ai-advisor/ask', [AiAdvisorController::class, 'ask'])->name('ai.ask')->middleware('throttle:ai-assistant');
    Route::post('/ai-advisor/{insight}/apply-budget', [AiAdvisorController::class, 'applyRecommendation'])->name('ai.apply');
    Route::patch('/ai-advisor/{insight}/dismiss', [AiAdvisorController::class, 'dismiss'])->name('ai.dismiss');
    Route::delete('/ai-advisor/chat', [AiAdvisorController::class, 'clearChat'])->name('ai.clear-chat');
    Route::get('/ai-advisor/summary/download', [AiAdvisorController::class, 'downloadSummary'])->name('ai.download-summary');

    // User Profile & Settings
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Categories (Domain Managed)
    Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [\App\Http\Controllers\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [\App\Http\Controllers\CategoryController::class, 'destroy'])->name('categories.destroy');

    // Financial Goals
    Route::get('/goals', [\App\Http\Controllers\FinancialGoalController::class, 'index'])->name('goals.index');
    Route::post('/goals', [\App\Http\Controllers\FinancialGoalController::class, 'store'])->name('goals.store');
    Route::post('/goals/{goal}/deposit', [\App\Http\Controllers\FinancialGoalController::class, 'deposit'])->name('goals.deposit');
    Route::delete('/goals/{goal}', [\App\Http\Controllers\FinancialGoalController::class, 'destroy'])->name('goals.destroy');

    // Shortcuts for Income & Expenses filtered views
    Route::get('/transactions/income', function () {
        return redirect()->route('transactions.index', ['type' => 'income']);
    })->name('transactions.income');
    Route::get('/transactions/expenses', function () {
        return redirect()->route('transactions.index', ['type' => 'expense']);
    })->name('transactions.expenses');

    // Notifications & Alerts
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // Subscriptions & SaaS Plans
    Route::get('/subscriptions', function () {
        $subService = app(\App\Services\SubscriptionService::class);
        return view('subscriptions.index', [
            'plans' => $subService->getAvailablePlans(),
            'subscription' => $subService->getUserSubscription(auth()->user()),
        ]);
    })->name('subscriptions.index');
    Route::post('/subscriptions/upgrade', function (\Illuminate\Http\Request $request) {
        $request->validate(['plan' => 'required|in:free,pro,enterprise']);
        app(\App\Services\SubscriptionService::class)->upgradePlan(auth()->user(), $request->plan);
        return back()->with('success', 'Plan upgraded successfully.');
    })->name('subscriptions.upgrade');

    // Administration (RBAC Protected)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminController::class, 'index'])->name('index');
        Route::get('/overview', [\App\Http\Controllers\Admin\AdminController::class, 'index'])->name('overview');
        Route::get('/users', [\App\Http\Controllers\Admin\AdminController::class, 'users'])->name('users');
        Route::get('/roles', [\App\Http\Controllers\Admin\AdminController::class, 'roles'])->name('roles');
        Route::get('/categories', [\App\Http\Controllers\Admin\AdminController::class, 'categories'])->name('categories');
        Route::get('/subscriptions', [\App\Http\Controllers\Admin\AdminController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/ai-usage', [\App\Http\Controllers\Admin\AdminController::class, 'aiUsage'])->name('ai-usage');
        Route::get('/settings', [\App\Http\Controllers\Admin\AdminController::class, 'settings'])->name('settings');
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\AdminController::class, 'auditLogs'])->name('audit-logs');
    });
});
