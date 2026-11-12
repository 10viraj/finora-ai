<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'period',
        'month_year',
        'alert_threshold',
        'notify_on_exceed',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'alert_threshold' => 'integer',
            'notify_on_exceed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Calculate amount spent in this budget period/category
     */
    public function getSpentAttribute(): float
    {
        $monthYear = $this->month_year ?: now()->format('Y-m');

        $query = Transaction::query()
            ->where('user_id', $this->user_id)
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->where('transaction_date', 'like', "$monthYear%");

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Remaining budget amount
     */
    public function getRemainingAttribute(): float
    {
        return (float) $this->amount - $this->spent;
    }

    /**
     * Utilization percentage
     */
    public function getUtilizationPercentageAttribute(): float
    {
        if ($this->amount <= 0) {
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
        if ($pct >= 100) {
            return 'exceeded';
        }
        if ($pct >= $this->alert_threshold) {
            return 'warning';
        }
        return 'normal';
    }
}
