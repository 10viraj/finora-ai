<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($notification) {
            if (empty($notification->title)) {
                $notification->title = $notification->data['title'] ?? ($notification->message ? substr($notification->message, 0, 40) : 'Notification Alert');
            }
            if (empty($notification->message)) {
                $notification->message = $notification->data['message'] ?? 'Notification alert received.';
            }
            if (empty($notification->type) && !empty($notification->data['type'])) {
                $notification->type = $notification->data['type'];
            }
        });
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}

