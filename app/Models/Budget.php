<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'currency',
        'spending_limit',
        'period',
        'month_year',
        'start_date',
        'end_date',
        'alert_percentage',
        'alert_threshold',
        'status',
        'notify_on_exceed',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'spending_limit' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'alert_percentage' => 'integer',
            'alert_threshold' => 'integer',
            'status' => 'boolean',
            'notify_on_exceed' => 'boolean',
        ];
    }

    protected $attributes = [
        'currency' => 'INR',
        'status' => true,
        'notify_on_exceed' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function ($budget) {
            // Synchronize alert_percentage and alert_threshold
            if ($budget->alert_percentage && !$budget->alert_threshold) {
                $budget->alert_threshold = $budget->alert_percentage;
            } elseif ($budget->alert_threshold && !$budget->alert_percentage) {
                $budget->alert_percentage = $budget->alert_threshold;
            }

            // Synchronize date ranges with month_year
            if (empty($budget->start_date) && !empty($budget->month_year)) {
                try {
                    if ($budget->period === 'yearly') {
                        $dt = Carbon::createFromFormat('Y', $budget->month_year);
                        $budget->start_date = $dt->copy()->startOfYear()->toDateString();
                        $budget->end_date = $dt->copy()->endOfYear()->toDateString();
                    } else {
                        $dt = Carbon::createFromFormat('Y-m', $budget->month_year);
                        $budget->start_date = $dt->copy()->startOfMonth()->toDateString();
                        $budget->end_date = $dt->copy()->endOfMonth()->toDateString();
                    }
                } catch (\Throwable $e) {
                    // Ignore parse fallback
                }
            } elseif (!empty($budget->start_date) && empty($budget->month_year)) {
                $budget->month_year = Carbon::parse($budget->start_date)->format($budget->period === 'yearly' ? 'Y' : 'Y-m');
            }

            if ($budget->status === null) {
                $budget->status = true;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function budgetCategories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class);
    }

    /**
     * Effective spending limit (falls back to amount if not explicitly set)
     */
    public function getEffectiveLimitAttribute(): float
    {
        if ($this->spending_limit !== null && (float) $this->spending_limit > 0) {
            return (float) $this->spending_limit;
        }

        return (float) $this->amount;
    }

    /**
     * Calculate amount spent in this budget period and category
     */
    public function getSpentAttribute(): float
    {
        $query = Transaction::query()
            ->where('user_id', $this->user_id)
            ->where('type', 'expense')
            ->where('status', 'completed');

        if ($this->period === 'yearly') {
            $year = $this->month_year ? substr($this->month_year, 0, 4) : now()->format('Y');
            $query->whereYear('transaction_date', $year);
        } elseif ($this->period === 'quarterly') {
            $monthYear = $this->month_year ?: now()->format('Y-m');
            $dt = Carbon::createFromFormat('Y-m', substr($monthYear, 0, 7) ?: now()->format('Y-m'));
            $startOfQuarter = $dt->copy()->startOfQuarter()->format('Y-m-d');
            $endOfQuarter = $dt->copy()->endOfQuarter()->format('Y-m-d');
            $query->whereBetween('transaction_date', [$startOfQuarter, $endOfQuarter]);
        } else {
            // Monthly
            $monthYear = $this->month_year ?: now()->format('Y-m');
            $query->where('transaction_date', 'like', "$monthYear%");
        }

        if ($this->category_id) {
            $childCategoryIds = Category::where('parent_id', $this->category_id)->pluck('id')->toArray();
            $allCategoryIds = array_merge([$this->category_id], $childCategoryIds);
            $query->whereIn('category_id', $allCategoryIds);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Remaining spending power based on effective spending limit
     */
    public function getRemainingAttribute(): float
    {
        return (float) $this->effective_limit - $this->spent;
    }

    /**
     * Utilization percentage against effective spending limit
     */
    public function getUtilizationPercentageAttribute(): float
    {
        $limit = $this->effective_limit;
        if ($limit <= 0) {
            return 0.0;
        }

        return round(($this->spent / $limit) * 100, 1);
    }

    /**
     * Utilization percentage against base allocated budget
     */
    public function getTargetUtilizationPercentageAttribute(): float
    {
        if ((float) $this->amount <= 0) {
            return 0.0;
        }

        return round(($this->spent / (float) $this->amount) * 100, 1);
    }

    /**
     * Status indicator: 'normal', 'warning', 'exceeded'
     */
    public function getStatusAttribute(): string
    {
        $pct = $this->utilization_percentage;
        if ($pct >= 100 || $this->spent >= $this->effective_limit) {
            return 'exceeded';
        }
        if ($pct >= $this->alert_threshold) {
            return 'warning';
        }
        return 'normal';
    }

    /**
     * Variance: Allocated Budget - Actual Spent
     * Positive = Favorable (Under Budget / Saved)
     * Negative = Unfavorable (Over Budget)
     */
    public function getVarianceAmountAttribute(): float
    {
        return (float) $this->amount - $this->spent;
    }

    /**
     * Variance percentage against base budget
     */
    public function getVariancePercentageAttribute(): float
    {
        if ((float) $this->amount <= 0) {
            return 0.0;
        }

        return round((($this->spent - (float) $this->amount) / (float) $this->amount) * 100, 1);
    }

    /**
     * Whether spending is within allocated budget
     */
    public function getIsFavorableAttribute(): bool
    {
        return $this->spent <= (float) $this->amount;
    }

    /**
     * Overspent dollar amount if exceeded
     */
    public function getOverspentAmountAttribute(): float
    {
        return max(0.0, $this->spent - $this->effective_limit);
    }

    /**
     * Days remaining in current budget cycle
     */
    public function getDaysRemainingAttribute(): int
    {
        $now = Carbon::now();

        if ($this->period === 'yearly') {
            $year = $this->month_year ? (int) substr($this->month_year, 0, 4) : $now->year;
            $endOfYear = Carbon::create($year, 12, 31, 23, 59, 59);
            return $now->greaterThan($endOfYear) ? 0 : max(1, (int) $now->diffInDays($endOfYear));
        }

        // Monthly
        $monthYear = $this->month_year ?: $now->format('Y-m');
        try {
            $endOfMonth = Carbon::createFromFormat('Y-m', $monthYear)->endOfMonth();
            return $now->greaterThan($endOfMonth) ? 0 : max(1, (int) $now->diffInDays($endOfMonth));
        } catch (\Exception $e) {
            return max(1, (int) $now->diffInDays($now->copy()->endOfMonth()));
        }
    }

    /**
     * Recommended daily spending limit for remaining days
     */
    public function getDailyBudgetAttribute(): float
    {
        if ($this->remaining <= 0) {
            return 0.0;
        }

        $days = max(1, $this->days_remaining);
        return round($this->remaining / $days, 2);
    }

    /**
     * Formatted Period Label
     */
    public function getPeriodLabelAttribute(): string
    {
        if ($this->period === 'yearly') {
            $yr = $this->month_year ? substr($this->month_year, 0, 4) : now()->format('Y');
            return "Annual ($yr)";
        }

        if ($this->period === 'quarterly') {
            return "Quarterly (" . ($this->month_year ?: now()->format('Y-m')) . ")";
        }

        // Monthly
        $my = $this->month_year ?: now()->format('Y-m');
        try {
            return Carbon::createFromFormat('Y-m', $my)->format('F Y');
        } catch (\Exception $e) {
            return $my;
        }
    }
}
