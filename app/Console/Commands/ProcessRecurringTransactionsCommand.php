<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessRecurringTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and duplicate scheduled recurring financial transactions for the active period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting recurring transaction processing...');

        $recurringTemplates = Transaction::where('is_recurring', true)
            ->where('status', 'completed')
            ->get();

        $processedCount = 0;
        $now = Carbon::now();

        foreach ($recurringTemplates as $template) {
            // Check if already processed for this period (e.g. this month for monthly recurrence)
            $interval = $template->recurrence_interval ?: 'monthly';
            $periodKey = match($interval) {
                'daily' => $now->format('Y-m-d'),
                'weekly' => $now->format('Y-W'),
                'monthly' => $now->format('Y-m'),
                'quarterly' => $now->year . '-Q' . $now->quarter,
                'yearly' => $now->format('Y'),
                default => $now->format('Y-m'),
            };

            // Check if another transaction with same user, account, amount, and reference already exists this month
            $exists = Transaction::where('user_id', $template->user_id)
                ->where('account_id', $template->account_id)
                ->where('description', $template->description)
                ->where('amount', $template->amount)
                ->where('transaction_date', 'like', $now->format('Y-m') . '%')
                ->where('id', '!=', $template->id)
                ->exists();

            if (!$exists && $template->transaction_date->format('Y-m') !== $now->format('Y-m')) {
                DB::transaction(function () use ($template, $now) {
                    $newTx = Transaction::create([
                        'user_id' => $template->user_id,
                        'account_id' => $template->account_id,
                        'destination_account_id' => $template->destination_account_id,
                        'category_id' => $template->category_id,
                        'type' => $template->type,
                        'amount' => $template->amount,
                        'fee' => $template->fee,
                        'transaction_date' => $now->format('Y-m-d'),
                        'description' => $template->description . ' [Auto-Recurring]',
                        'payee_payer' => $template->payee_payer,
                        'payment_method' => $template->payment_method,
                        'reference_number' => 'REC-' . $now->format('Ymd') . '-' . $template->id,
                        'notes' => 'Generated automatically by recurring transaction scheduler.',
                        'is_recurring' => true,
                        'recurrence_interval' => $template->recurrence_interval,
                        'status' => 'completed',
                    ]);

                    Account::find($template->account_id)?->recalculateBalance();
                    if ($template->destination_account_id) {
                        Account::find($template->destination_account_id)?->recalculateBalance();
                    }
                });

                $processedCount++;
            }
        }

        $this->info("Completed. {$processedCount} recurring transactions processed.");

        return Command::SUCCESS;
    }
}

