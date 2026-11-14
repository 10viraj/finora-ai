<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UnusualExpenseNotification extends Notification
{
    use Queueable;

    public function __construct(public Transaction $transaction, public float $historicalAverage) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'unusual_expense',
            'title' => "Unusual Expense: {$this->transaction->description}",
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
            'description' => $this->transaction->description,
            'historical_average' => $this->historicalAverage,
            'message' => "Unusual expense detected: {$this->transaction->description} ({$this->transaction->amount} {$this->transaction->currency}).",
        ];
    }
}

