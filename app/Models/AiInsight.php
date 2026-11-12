<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'summary',
        'detailed_analysis',
        'priority',
        'action_suggested',
        'metrics_snapshot',
        'is_dismissed',
        'action_taken',
    ];

    protected function casts(): array
    {
        return [
            'metrics_snapshot' => 'array',
            'is_dismissed' => 'boolean',
            'action_taken' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_dismissed', false);
    }
}
