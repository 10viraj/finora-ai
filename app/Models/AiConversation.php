<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'title',
        'context_type',
        'status',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->oldest();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

