<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'business_name',
        'currency',
        'phone',
        'ai_provider',
        'ai_api_key',
        'ai_model',
        'ai_base_url',
        'subscription_plan',
        'subscription_status',
        'subscription_expires_at',
        'daily_ai_queries_count',
        'last_ai_query_date',
    ];

    protected $attributes = [
        'currency' => 'INR',
        'role' => 'individual',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'ai_api_key',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ai_api_key' => 'encrypted',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(FinancialGoal::class);
    }

    public function financialGoals(): HasMany
    {
        return $this->hasMany(FinancialGoal::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function aiInsights(): HasMany
    {
        return $this->hasMany(AiInsight::class)->latest();
    }

    public function aiChatMessages(): HasMany
    {
        return $this->hasMany(AiChatMessage::class)->oldest();
    }

    public function monthlyFinancialSummaries(): HasMany
    {
        return $this->hasMany(MonthlyFinancialSummary::class)->latest('month_year');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(RecurringTransaction::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class)->latest();
    }

    public function aiMessages(): HasMany
    {
        return $this->hasMany(AiMessage::class)->latest();
    }

    public function aiRecommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class)->latest();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->latest('created_at');
    }

    /**
     * Total current balance across all active accounts
     */
    public function getTotalBalanceAttribute(): float
    {
        return (float) $this->accounts()->where('is_active', true)->sum('balance');
    }

    /**
     * Monthly income for given or current month (YYYY-MM)
     */
    public function getMonthlyIncome(?string $monthYear = null): float
    {
        $monthYear = $monthYear ?: now()->format('Y-m');
        return (float) $this->transactions()
            ->where('type', 'income')
            ->where('status', 'completed')
            ->where('transaction_date', 'like', "$monthYear%")
            ->sum('amount');
    }

    /**
     * Monthly expenses for given or current month (YYYY-MM)
     */
    public function getMonthlyExpenses(?string $monthYear = null): float
    {
        $monthYear = $monthYear ?: now()->format('Y-m');
        return (float) $this->transactions()
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->where('transaction_date', 'like', "$monthYear%")
            ->sum('amount');
    }

    /**
     * Monthly net savings
     */
    public function getMonthlySavings(?string $monthYear = null): float
    {
        return $this->getMonthlyIncome($monthYear) - $this->getMonthlyExpenses($monthYear);
    }

    /**
     * Monthly savings rate as a percentage
     */
    public function getSavingsRate(?string $monthYear = null): float
    {
        $income = $this->getMonthlyIncome($monthYear);
        if ($income <= 0) {
            return 0.0;
        }
        $savings = $this->getMonthlySavings($monthYear);
        return round(($savings / $income) * 100, 1);
    }
}
