<?php

namespace App\Models\Traits;

use App\Models\Scopes\UserScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    /**
     * Boot the BelongsToUser trait for a model.
     */
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope(new UserScope);

        static::creating(function ($model) {
            if (empty($model->user_id) && Auth::hasUser()) {
                $model->user_id = Auth::id();
            }
        });
    }

    /**
     * Get the owner user of this record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Query records bypassing the global user scope.
     */
    public function scopeWithoutUserScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(UserScope::class);
    }
}

