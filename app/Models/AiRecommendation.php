<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendation extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'insight_id',
        'category_id',
        'title',
        'recommendation_type',
        'suggested_amount',
        'potential_savings',
        'priority',
        'status',
        'action_payload',
    ];

    protected function casts(): array
    {
        return [
            'suggested_amount' => 'decimal:2',
            'potential_savings' => 'decimal:2',
            'action_payload' => 'array',
        ];
    }

    public function insight(): BelongsTo
    {
        return $this->belongsTo(AiInsight::class, 'insight_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }
}

