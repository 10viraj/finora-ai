<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_name',
        'name',
        'account_type',
        'type',
        'account_number_last4',
        'opening_balance',
        'balance',
        'initial_balance',
        'currency',
        'color',
        'description',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'balance' => 'decimal:2',
            'initial_balance' => 'decimal:2',
            'status' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected $attributes = [
        'currency' => 'INR',
        'status' => true,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function ($account) {
            // Sync account_name and name
            if (!empty($account->account_name) && empty($account->name)) {
                $account->name = $account->account_name;
            } elseif (!empty($account->name) && empty($account->account_name)) {
                $account->account_name = $account->name;
            }

            // Sync account_type and type
            if (!empty($account->account_type) && empty($account->type)) {
                $account->type = $account->account_type;
            } elseif (!empty($account->type) && empty($account->account_type)) {
                $account->account_type = $account->type;
            }

            // Sync opening_balance and initial_balance
            if (isset($account->opening_balance) && !isset($account->initial_balance)) {
                $account->initial_balance = $account->opening_balance;
            } elseif (isset($account->initial_balance) && !isset($account->opening_balance)) {
                $account->opening_balance = $account->initial_balance;
            }

            // Sync status and is_active
            if (isset($account->status) && !isset($account->is_active)) {
                $account->is_active = $account->status;
            } elseif (isset($account->is_active) && !isset($account->status)) {
                $account->status = $account->is_active;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function destinationTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }

    /**
     * Recalculate balance based on initial balance and all completed transactions
     */
    public function recalculateBalance(): void
    {
        $incomes = (float) $this->transactions()
            ->where('type', 'income')
            ->where('status', 'completed')
            ->sum('amount');

        $expenses = (float) $this->transactions()
            ->where('type', 'expense')
            ->where('status', 'completed')
            ->selectRaw('SUM(amount + fee) as total')
            ->value('total');

        $transfersOut = (float) $this->transactions()
            ->where('type', 'transfer')
            ->where('status', 'completed')
            ->selectRaw('SUM(amount + fee) as total')
            ->value('total');

        $transfersIn = (float) $this->destinationTransactions()
            ->where('type', 'transfer')
            ->where('status', 'completed')
            ->sum('amount');

        $newBalance = (float) $this->initial_balance + $incomes + $transfersIn - $expenses - $transfersOut;

        $this->update(['balance' => $newBalance]);
    }
}

