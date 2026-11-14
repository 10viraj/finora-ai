<?php

namespace App\Jobs;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessRecurringTransactions implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = now()->format('Y-m-d');
        $dueItems = RecurringTransaction::withoutUserScope()
            ->active()
            ->whereDate('next_run_date', '<=', $today)
            ->get();

        foreach ($dueItems as $rec) {
            DB::transaction(function () use ($rec) {
                // Record the financial transaction
                Transaction::create([
                    'user_id' => $rec->user_id,
                    'account_id' => $rec->account_id,
                    'destination_account_id' => $rec->destination_account_id,
                    'category_id' => $rec->category_id,
                    'payment_method_id' => $rec->payment_method_id,
                    'type' => $rec->type,
                    'amount' => $rec->amount,
                    'fee' => $rec->fee,
                    'transaction_date' => now()->format('Y-m-d'),
                    'description' => '[Auto-Recurring] ' . $rec->description,
                    'payee_payer' => $rec->payee_payer,
                    'payment_method' => $rec->payment_method,
                    'is_recurring' => true,
                    'status' => 'completed',
                ]);

                // Update account balances
                $rec->account?->recalculateBalance();
                if ($rec->destinationAccount) {
                    $rec->destinationAccount->recalculateBalance();
                }

                // Compute next run date
                $next = Carbon::parse($rec->next_run_date);
                $next = match ($rec->recurrence_interval) {
                    'daily' => $next->addDay(),
                    'weekly' => $next->addWeek(),
                    'biweekly' => $next->addWeeks(2),
                    'quarterly' => $next->addMonths(3),
                    'yearly' => $next->addYear(),
                    default => $next->addMonth(),
                };

                $rec->update([
                    'last_run_date' => now()->format('Y-m-d'),
                    'next_run_date' => $next->format('Y-m-d'),
                ]);
            });
        }
    }
}

