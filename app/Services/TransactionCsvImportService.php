<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TransactionCsvImportService
{
    /**
     * Import transactions from a CSV file.
     */
    public function import(User $user, UploadedFile|string $file, ?int $defaultAccountId = null): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();
        if (!file_exists($filePath)) {
            return ['success' => false, 'imported' => 0, 'skipped' => 0, 'errors' => ['File not found.']];
        }

        $account = $defaultAccountId ? $user->accounts()->find($defaultAccountId) : $user->accounts()->first();
        if (!$account) {
            $account = $user->accounts()->create([
                'name' => 'Default Bank Account',
                'type' => 'checking',
                'balance' => 0,
                'initial_balance' => 0,
                'currency' => $user->currency,
                'is_active' => true,
            ]);
        }

        $categories = Category::forUser($user->id)->get();
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'imported' => 0, 'skipped' => 0, 'errors' => ['Unable to open CSV file.']];
        }

        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'imported' => 0, 'skipped' => 0, 'errors' => ['Empty CSV file.']];
        }

        // Normalize header column indices
        $indices = $this->mapHeaders($header);

        DB::beginTransaction();
        try {
            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) {
                    continue; // Skip empty row
                }

                // Extract fields
                $rawDate = isset($indices['date']) && isset($row[$indices['date']]) ? trim($row[$indices['date']]) : null;
                $description = isset($indices['description']) && isset($row[$indices['description']]) ? trim($row[$indices['description']]) : 'Imported Transaction';
                $rawAmount = isset($indices['amount']) && isset($row[$indices['amount']]) ? trim($row[$indices['amount']]) : '0';
                $rawType = isset($indices['type']) && isset($row[$indices['type']]) ? strtolower(trim($row[$indices['type']])) : null;
                $rawCategory = isset($indices['category']) && isset($row[$indices['category']]) ? trim($row[$indices['category']]) : null;
                $paymentMethod = isset($indices['payment_method']) && isset($row[$indices['payment_method']]) ? trim($row[$indices['payment_method']]) : 'bank_transfer';

                // Parse date
                $date = now()->format('Y-m-d');
                if ($rawDate) {
                    try {
                        $date = Carbon::parse($rawDate)->format('Y-m-d');
                    } catch (\Throwable $e) {}
                }

                // Parse amount & determine type
                $cleanAmount = preg_replace('/[^\d\.\-]/', '', $rawAmount);
                $amountVal = (float) $cleanAmount;
                $type = $rawType ?: ($amountVal < 0 ? 'expense' : 'income');
                $finalAmount = abs($amountVal);

                if ($finalAmount <= 0) {
                    $skipped++;
                    continue;
                }

                // Map category
                $categoryId = null;
                if ($rawCategory) {
                    $matchedCat = $categories->first(fn($c) => strcasecmp($c->name, $rawCategory) === 0);
                    if ($matchedCat) {
                        $categoryId = $matchedCat->id;
                    }
                }

                // Duplicate detection
                $isDuplicate = Transaction::where('user_id', $user->id)
                    ->where('account_id', $account->id)
                    ->where('transaction_date', $date)
                    ->where('amount', $finalAmount)
                    ->where('description', $description)
                    ->exists();

                if ($isDuplicate) {
                    $skipped++;
                    continue;
                }

                // Create transaction
                $tx = Transaction::create([
                    'user_id' => $user->id,
                    'account_id' => $account->id,
                    'category_id' => $categoryId,
                    'type' => $type,
                    'amount' => $finalAmount,
                    'transaction_date' => $date,
                    'description' => $description,
                    'payment_method' => $paymentMethod,
                    'currency' => $account->currency ?: $user->currency,
                    'status' => 'completed',
                ]);

                $imported++;
            }

            // Recalculate account balance once after all rows processed
            $account->recalculateBalance();

            // Record audit log
            AuditLog::record('transactions.csv_import', $account, null, [
                'imported_count' => $imported,
                'skipped_count' => $skipped,
            ], $user->id);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = $e->getMessage();
        } finally {
            fclose($handle);
        }

        return [
            'success' => count($errors) === 0,
            'imported' => $imported,
            'skipped' => $skipped,
            'account_id' => $account->id,
            'errors' => $errors,
        ];
    }

    /**
     * Map CSV header row columns to transaction fields.
     */
    protected function mapHeaders(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $colName) {
            $col = strtolower(trim($colName));
            if (in_array($col, ['date', 'transaction_date', 'trans_date', 'tx_date', 'time'])) {
                $map['date'] = $index;
            } elseif (in_array($col, ['description', 'details', 'merchant', 'payee', 'narrative', 'title', 'memo'])) {
                $map['description'] = $index;
            } elseif (in_array($col, ['amount', 'value', 'total', 'debit', 'sum'])) {
                $map['amount'] = $index;
            } elseif (in_array($col, ['type', 'transaction_type', 'dr/cr', 'direction'])) {
                $map['type'] = $index;
            } elseif (in_array($col, ['category', 'category_name', 'classification'])) {
                $map['category'] = $index;
            } elseif (in_array($col, ['payment_method', 'method', 'mode', 'channel'])) {
                $map['payment_method'] = $index;
            }
        }

        return $map;
    }
}

