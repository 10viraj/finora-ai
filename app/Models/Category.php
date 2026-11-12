<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'icon',
        'color',
        'parent_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Hierarchical display name: "Parent Category > Subcategory"
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->parent_id && $this->relationLoaded('parent') && $this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }

        return $this->name;
    }

    public function scopeParentsOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id')->orWhere('user_id', $userId);
        });
    }
}
