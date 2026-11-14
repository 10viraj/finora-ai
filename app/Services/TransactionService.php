<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransactionService
{
    /**
     * Record a new financial transaction with account balance adjustments and receipt attachment.
     */
    public function recordTransaction(User $user, array $data, ?UploadedFile $receipt = null): Transaction
    {
        $receiptPath = null;
        if ($receipt) {
            $receiptPath = $receipt->store('receipts/' . $user->id, 'local');
        }

        $paymentMethodId = $data['payment_method_id'] ?? null;
        if (!$paymentMethodId && !empty($data['payment_method'])) {
            $paymentMethodId = PaymentMethod::where('code', $data['payment_method'])->value('id');
        }

        return DB::transaction(function () use ($user, $data, $receiptPath, $paymentMethodId, $receipt) {
            $tx = $user->transactions()->create([
                'account_id' => $data['account_id'],
                'destination_account_id' => $data['destination_account_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'payment_method_id' => $paymentMethodId,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? $user->currency ?? 'INR',
                'fee' => $data['fee'] ?? 0,
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'payee_payer' => $data['payee_payer'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'reference_number' => $data['reference_number'] ?? null,
                'receipt_path' => $receiptPath,
                'notes' => $data['notes'] ?? null,
                'is_recurring' => (bool) ($data['is_recurring'] ?? false),
                'recurrence_interval' => !empty($data['is_recurring']) ? ($data['recurrence_interval'] ?? 'monthly') : null,
                'status' => 'completed',
            ]);

            // Create attachment if receipt uploaded
            if ($receiptPath && $receipt) {
                Attachment::create([
                    'user_id' => $user->id,
                    'attachable_type' => Transaction::class,
                    'attachable_id' => $tx->id,
                    'file_name' => $receipt->getClientOriginalName(),
                    'file_path' => $receiptPath,
                    'file_type' => $receipt->getClientMimeType() ?: 'application/octet-stream',
                    'file_size' => $receipt->getSize() ?: 1024,
                    'description' => 'Uploaded transaction receipt voucher',
                ]);
            }

            // Recalculate affected account balances
            Account::find($data['account_id'])?->recalculateBalance();
            if (!empty($data['destination_account_id'])) {
                Account::find($data['destination_account_id'])?->recalculateBalance();
            }

            // Record in audit log
            AuditLog::record('transaction.created', $tx, null, $tx->toArray(), $user->id);

            // Statistical anomaly detection (non-blocking evaluation)
            try {
                app(\App\Services\AnomalyDetectionService::class)->evaluateTransaction($tx);
            } catch (\Throwable $e) {
                // Do not fail transaction on anomaly check warning
            }

            return $tx;
        });
    }

    /**
     * Update an existing transaction and re-sync balance calculations.
     */
    public function updateTransaction(Transaction $transaction, array $data, ?UploadedFile $receipt = null): Transaction
    {
        $oldSourceId = $transaction->account_id;
        $oldDestId = $transaction->destination_account_id;
        $oldValues = $transaction->toArray();

        $paymentMethodId = $data['payment_method_id'] ?? null;
        if (!$paymentMethodId && !empty($data['payment_method'])) {
            $paymentMethodId = PaymentMethod::where('code', $data['payment_method'])->value('id');
        }

        return DB::transaction(function () use ($transaction, $data, $receipt, $oldSourceId, $oldDestId, $paymentMethodId, $oldValues) {
            $updateData = [
                'account_id' => $data['account_id'],
                'destination_account_id' => $data['destination_account_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'payment_method_id' => $paymentMethodId,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'fee' => $data['fee'] ?? 0,
                'transaction_date' => $data['transaction_date'],
                'description' => $data['description'],
                'payee_payer' => $data['payee_payer'] ?? null,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_recurring' => (bool) ($data['is_recurring'] ?? false),
                'recurrence_interval' => !empty($data['is_recurring']) ? ($data['recurrence_interval'] ?? 'monthly') : null,
            ];

            if ($receipt) {
                if ($transaction->receipt_path && Storage::disk('local')->exists($transaction->receipt_path)) {
                    Storage::disk('local')->delete($transaction->receipt_path);
                }
                $updateData['receipt_path'] = $receipt->store('receipts/' . $transaction->user_id, 'local');
            }

            $transaction->update($updateData);

            // Recalculate balances
            Account::find($oldSourceId)?->recalculateBalance();
            if ($oldDestId && $oldDestId !== $oldSourceId) {
                Account::find($oldDestId)?->recalculateBalance();
            }
            if ($data['account_id'] !== $oldSourceId) {
                Account::find($data['account_id'])?->recalculateBalance();
            }
            if (!empty($data['destination_account_id']) && $data['destination_account_id'] !== $oldDestId) {
                Account::find($data['destination_account_id'])?->recalculateBalance();
            }

            // Record audit log
            AuditLog::record('transaction.updated', $transaction, $oldValues, $transaction->fresh()->toArray(), $transaction->user_id);

            return $transaction;
        });
    }

    /**
     * Soft delete a transaction and restore balance.
     */
    public function deleteTransaction(Transaction $transaction): bool
    {
        $sourceId = $transaction->account_id;
        $destId = $transaction->destination_account_id;
        $receiptPath = $transaction->receipt_path;
        $oldValues = $transaction->toArray();

        return DB::transaction(function () use ($transaction, $sourceId, $destId, $receiptPath, $oldValues) {
            if ($receiptPath && Storage::disk('local')->exists($receiptPath)) {
                Storage::disk('local')->delete($receiptPath);
            }

            $deleted = $transaction->delete();

            Account::find($sourceId)?->recalculateBalance();
            if ($destId) {
                Account::find($destId)?->recalculateBalance();
            }

            // Record audit log
            AuditLog::record('transaction.deleted', $transaction, $oldValues, null, $transaction->user_id);

            return (bool) $deleted;
        });
    }
}

