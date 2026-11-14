<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiMessage;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    /**
     * Admin Overview Dashboard.
     */
    public function index()
    {
        $this->authorizeAdmin();

        $totalUsers = User::count();
        $roles = Role::withCount('users')->get();
        $totalTransactions = Transaction::withoutUserScope()->count();
        $totalVolume = (float) Transaction::withoutUserScope()->sum('amount');
        $recentAuditLogs = AuditLog::with('user')->latest('created_at')->limit(10)->get();

        $todayAiQueries = AiMessage::whereDate('created_at', now()->toDateString())->count();
        $totalAiCostUsd = (float) AiMessage::sum('cost_usd');

        return view('admin.index', compact(
            'totalUsers',
            'roles',
            'totalTransactions',
            'totalVolume',
            'recentAuditLogs',
            'todayAiQueries',
            'totalAiCostUsd'
        ));
    }

    /**
     * User Management.
     */
    public function users(Request $request)
    {
        $this->authorizeAdmin();

        $query = User::withCount('transactions', 'accounts');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users', compact('users'));
    }

    /**
     * Roles & Permissions.
     */
    public function roles()
    {
        $this->authorizeAdmin();

        $roles = Role::with(['permissions', 'users'])->get();
        $permissions = Permission::all();

        return view('admin.roles', compact('roles', 'permissions'));
    }

    /**
     * Category Management.
     */
    public function categories()
    {
        $this->authorizeAdmin();

        $systemCategories = Category::whereNull('user_id')->withCount('transactions')->get();
        $userCategoriesCount = Category::whereNotNull('user_id')->count();

        return view('admin.categories', compact('systemCategories', 'userCategoriesCount'));
    }

    /**
     * Subscription Plans Configuration.
     */
    public function subscriptions()
    {
        $this->authorizeAdmin();

        $subService = app(SubscriptionService::class);
        $plans = $subService->getAvailablePlans();

        $subscribersByPlan = User::selectRaw('subscription_plan, count(*) as count')
            ->groupBy('subscription_plan')
            ->pluck('count', 'subscription_plan')
            ->toArray();

        return view('admin.subscriptions', compact('plans', 'subscribersByPlan'));
    }

    /**
     * AI Usage & Cost Analytics.
     */
    public function aiUsage()
    {
        $this->authorizeAdmin();

        $totalMessages = AiMessage::count();
        $totalTokens = (int) AiMessage::sum('tokens_used');
        $totalCostUsd = (float) AiMessage::sum('cost_usd');
        $totalCostInr = (float) AiMessage::sum('cost_inr');

        $recentAiMessages = AiMessage::with('user', 'conversation')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.ai-usage', compact(
            'totalMessages',
            'totalTokens',
            'totalCostUsd',
            'totalCostInr',
            'recentAiMessages'
        ));
    }

    /**
     * System Settings.
     */
    public function settings()
    {
        $this->authorizeAdmin();

        $settings = [
            'app_name' => config('app.name', 'FinTrack AI'),
            'app_env' => config('app.env'),
            'ai_model' => config('services.ai.model', 'gpt-4o-mini'),
            'ai_base_url' => config('services.ai.url', 'https://api.openai.com/v1'),
            'default_currency' => 'INR',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        return view('admin.settings', compact('settings'));
    }

    /**
     * Audit Logs.
     */
    public function auditLogs(Request $request)
    {
        $this->authorizeAdmin();

        $query = AuditLog::with('user')->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.audit-logs', compact('logs'));
    }

    protected function authorizeAdmin(): void
    {
        if (!auth()->check()) {
            abort(403, 'Unauthorized access to FinTrack Administration.');
        }

        $user = auth()->user();
        if (!$user->hasRole('admin') && $user->role !== 'admin') {
            abort(403, 'Unauthorized access to FinTrack Administration.');
        }
    }
}
