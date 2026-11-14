<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiInsight extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'insight_type',
        'type',
        'title',
        'description',
        'summary',
        'period_start',
        'period_end',
        'data_snapshot',
        'metrics_snapshot',
        'generated_at',
        'detailed_analysis',
        'priority',
        'action_suggested',
        'is_dismissed',
        'action_taken',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'data_snapshot' => 'array',
            'metrics_snapshot' => 'array',
            'generated_at' => 'datetime',
            'is_dismissed' => 'boolean',
            'action_taken' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function ($insight) {
            // Sync insight_type and type
            if (!empty($insight->insight_type) && empty($insight->type)) {
                $insight->type = $insight->insight_type;
            } elseif (!empty($insight->type) && empty($insight->insight_type)) {
                $insight->insight_type = $insight->type;
            }

            // Sync description and summary
            if (!empty($insight->description) && empty($insight->summary)) {
                $insight->summary = $insight->description;
            } elseif (!empty($insight->summary) && empty($insight->description)) {
                $insight->description = $insight->summary;
            }

            // Sync data_snapshot and metrics_snapshot
            if (!empty($insight->data_snapshot) && empty($insight->metrics_snapshot)) {
                $insight->metrics_snapshot = $insight->data_snapshot;
            } elseif (!empty($insight->metrics_snapshot) && empty($insight->data_snapshot)) {
                $insight->data_snapshot = $insight->metrics_snapshot;
            }

            if (empty($insight->generated_at)) {
                $insight->generated_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_dismissed', false);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(AiRecommendation::class, 'insight_id');
    }
}

