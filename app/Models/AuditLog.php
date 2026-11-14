<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new UserScope);

        static::creating(function ($model) {
            if (empty($model->user_id) && Auth::hasUser()) {
                $model->user_id = Auth::id();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?Model $entity = null, ?array $oldValues = null, ?array $newValues = null, ?int $userId = null): self
    {
        return static::create([
            'user_id' => $userId ?: (Auth::hasUser() ? Auth::id() : null),
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity ? $entity->getKey() : null,
            'old_values' => static::sanitizeValues($oldValues),
            'new_values' => static::sanitizeValues($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Sanitize and redact sensitive keys to prevent credential or sensitive data leakage in audit logs.
     */
    public static function sanitizeValues(?array $values): ?array
    {
        if (!$values) {
            return null;
        }

        $sensitive = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'remember_token',
            'secret',
            'ai_api_key',
            'api_key',
            'pin',
            'cvv',
            'card_number',
            'pan',
        ];

        foreach ($values as $key => $val) {
            if (in_array(strtolower($key), $sensitive, true)) {
                $values[$key] = '[REDACTED]';
            } elseif (is_array($val)) {
                $values[$key] = static::sanitizeValues($val);
            }
        }

        return $values;
    }
}

