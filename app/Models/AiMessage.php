<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'role',
        'content',
        'message',
        'token_usage',
        'tokens_used',
        'data_payload',
    ];

    protected function casts(): array
    {
        return [
            'data_payload' => 'array',
            'token_usage' => 'integer',
            'tokens_used' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function ($msg) {
            // Sync content and message
            if (!empty($msg->content) && empty($msg->message)) {
                $msg->message = $msg->content;
            } elseif (!empty($msg->message) && empty($msg->content)) {
                $msg->content = $msg->message;
            }

            // Sync token_usage and tokens_used
            if (isset($msg->token_usage) && !isset($msg->tokens_used)) {
                $msg->tokens_used = $msg->token_usage;
            } elseif (isset($msg->tokens_used) && !isset($msg->token_usage)) {
                $msg->token_usage = $msg->tokens_used;
            }
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}

