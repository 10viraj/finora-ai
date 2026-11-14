<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Transaction extends Model
{
    use HasFactory, BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id',
        'account_id',
        'destination_account_id',
        'category_id',
        'payment_method_id',
        'type',
        'amount',
        'currency',
        'fee',
        'transaction_date',
        'description',
        'payee_payer',
        'payment_method',
        'reference_number',
        'receipt_path',
        'notes',
        'is_recurring',
        'recurrence_interval',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'transaction_date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    protected $attributes = [
        'currency' => 'INR',
        'status' => 'completed',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function paymentMethodRelation(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function getFormattedPaymentMethodAttribute(): string
    {
        $names = [
            'bank_transfer' => 'Bank Transfer (ACH/Wire)',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'cash' => 'Cash / Petty Cash',
            'upi' => 'UPI / Mobile Pay',
            'paypal' => 'PayPal',
            'stripe' => 'Stripe',
            'check' => 'Check',
            'other' => 'Other',
        ];

        return $names[$this->payment_method] ?? ucwords(str_replace('_', ' ', $this->payment_method));
    }

    public function hasReceipt(): bool
    {
        return !empty($this->receipt_path) && Storage::disk('local')->exists($this->receipt_path);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeTransfer($query)
    {
        return $query->where('type', 'transfer');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeForMonth($query, $monthYear)
    {
        return $query->where('transaction_date', 'like', "$monthYear%");
    }
}
