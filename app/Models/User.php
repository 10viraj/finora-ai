<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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

    public function goals(): HasMany
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
