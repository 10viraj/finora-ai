<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyFinancialSummary extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'month_year',
        'total_income',
        'total_expenses',
        'net_savings',
        'savings_rate',
        'health_score',
        'executive_summary',
        'spending_highlights',
        'top_categories',
        'recommendations',
    ];

    protected function casts(): array
    {
        return [
            'total_income' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'net_savings' => 'decimal:2',
            'savings_rate' => 'decimal:2',
            'health_score' => 'integer',
            'spending_highlights' => 'array',
            'top_categories' => 'array',
            'recommendations' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedMonthAttribute(): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $this->month_year)->format('F Y');
        } catch (\Exception $e) {
            return $this->month_year;
        }
    }
}

