<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'account_number_last4',
        'balance',
        'initial_balance',
        'currency',
        'color',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'initial_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
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
