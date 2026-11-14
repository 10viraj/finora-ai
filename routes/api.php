<?php

use App\Http\Controllers\Api\AiApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\TransactionApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes (FinTrack AI / finora-ai)
|--------------------------------------------------------------------------
| Prefix: /api/v1
| Authentication: Laravel Sanctum (Bearer Token)
| Throttling: 10/min on Auth endpoints, 60/min on Protected Resources
*/

Route::prefix('v1')->group(function () {

    // ==========================================
    // 1. Authentication APIs (Public / Throttled)
    // ==========================================
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    });

    // ==========================================
    // Protected API Endpoints (auth:sanctum)
    // ==========================================
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

        // User & Session Management
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Dashboard Summary Endpoint
        Route::get('/dashboard/summary', [DashboardApiController::class, 'summary']);

        // ==========================================
        // 2. Transaction APIs
        // ==========================================
        Route::get('/transactions/summary', [TransactionApiController::class, 'summary']);
        Route::get('/transactions', [TransactionApiController::class, 'index']);
        Route::post('/transactions', [TransactionApiController::class, 'store']);
        Route::post('/transactions/import/csv', [TransactionApiController::class, 'importCsv']);
        Route::post('/receipts/scan', [TransactionApiController::class, 'scanReceipt']);
        Route::get('/transactions/{id}', [TransactionApiController::class, 'show'])->whereNumber('id');
        Route::put('/transactions/{id}', [TransactionApiController::class, 'update'])->whereNumber('id');
        Route::delete('/transactions/{id}', [TransactionApiController::class, 'destroy'])->whereNumber('id');

        // ==========================================
        // 3. Budget APIs
        // ==========================================
        Route::get('/budgets/progress', [BudgetApiController::class, 'progress']);
        Route::get('/budgets/comparison', [BudgetApiController::class, 'comparison']);
        Route::get('/budgets/alerts', [BudgetApiController::class, 'alerts']);
        Route::get('/budgets', [BudgetApiController::class, 'index']);
        Route::post('/budgets', [BudgetApiController::class, 'store']);
        Route::get('/budgets/{id}/progress', [BudgetApiController::class, 'progress'])->whereNumber('id');
        Route::get('/budgets/{id}', [BudgetApiController::class, 'show'])->whereNumber('id');
        Route::put('/budgets/{id}', [BudgetApiController::class, 'update'])->whereNumber('id');
        Route::delete('/budgets/{id}', [BudgetApiController::class, 'destroy'])->whereNumber('id');

        // ==========================================
        // 4. Financial Goals APIs
        // ==========================================
        Route::get('/goals', [\App\Http\Controllers\Api\GoalApiController::class, 'index']);
        Route::post('/goals', [\App\Http\Controllers\Api\GoalApiController::class, 'store']);
        Route::get('/goals/{id}', [\App\Http\Controllers\Api\GoalApiController::class, 'show'])->whereNumber('id');
        Route::put('/goals/{id}', [\App\Http\Controllers\Api\GoalApiController::class, 'update'])->whereNumber('id');
        Route::delete('/goals/{id}', [\App\Http\Controllers\Api\GoalApiController::class, 'destroy'])->whereNumber('id');
        Route::post('/goals/{id}/deposit', [\App\Http\Controllers\Api\GoalApiController::class, 'deposit'])->whereNumber('id');

        // ==========================================
        // 5. Multi-Currency APIs
        // ==========================================
        Route::get('/currencies/rates', [\App\Http\Controllers\Api\CurrencyApiController::class, 'rates']);
        Route::post('/currencies/convert', [\App\Http\Controllers\Api\CurrencyApiController::class, 'convert']);

        // ==========================================
        // 6. Notifications & Alerts APIs
        // ==========================================
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationApiController::class, 'index']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationApiController::class, 'markAsRead'])->whereNumber('id');

        // ==========================================
        // 7. Subscriptions & SaaS Management APIs
        // ==========================================
        Route::get('/subscription', [\App\Http\Controllers\Api\SubscriptionApiController::class, 'index']);
        Route::post('/subscription/upgrade', [\App\Http\Controllers\Api\SubscriptionApiController::class, 'upgrade']);

        // ==========================================
        // 8. Audit Trail APIs
        // ==========================================
        Route::get('/audit-logs', [\App\Http\Controllers\Api\AuditLogApiController::class, 'index']);

        // ==========================================
        // 9. AI APIs (Throttled for Security & Quotas)
        // ==========================================
        Route::middleware('throttle:ai-assistant')->group(function () {
            Route::post('/ai/chat', [AiApiController::class, 'chat']);
            Route::get('/ai/conversations', [AiApiController::class, 'conversations']);
            Route::get('/ai/insights', [AiApiController::class, 'insights']);
            Route::post('/ai/analyze', [AiApiController::class, 'analyze']);
            Route::get('/ai/recommendations', [AiApiController::class, 'recommendations']);
            Route::post('/ai/summarize', [AiApiController::class, 'summarize']);

            // Backward-compatible AI aliases
            Route::post('/ai/ask', [AiApiController::class, 'chat']);
            Route::post('/ai/generate', [AiApiController::class, 'analyze']);
            Route::get('/ai/summary', [AiApiController::class, 'summarize']);
        });

        // ==========================================
        // 10. Report APIs
        // ==========================================
        Route::get('/reports/monthly', [ReportApiController::class, 'monthly']);
        Route::get('/reports/yearly', [ReportApiController::class, 'yearly']);
        Route::get('/reports/categories', [ReportApiController::class, 'categories']);
        Route::get('/reports/export/pdf', [ReportApiController::class, 'exportPdf']);
        Route::get('/reports/export/excel', [ReportApiController::class, 'exportExcel']);

        // Backward-compatible Report aliases
        Route::get('/reports/summary', [ReportApiController::class, 'summary']);
        Route::get('/reports/trends', [ReportApiController::class, 'trends']);
        Route::get('/reports/category-expenses', [ReportApiController::class, 'categoryExpenses']);
    });
});
