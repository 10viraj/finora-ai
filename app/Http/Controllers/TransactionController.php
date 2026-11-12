<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->transactions()->with(['account', 'destinationAccount', 'category.parent']);

        // Filtering by Type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtering by Account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Filtering by Category / Subcategory
        if ($request->filled('category_id')) {
            $catId = $request->category_id;
            // Match category or any of its children subcategories
            $childCategoryIds = Category::where('parent_id', $catId)->pluck('id')->toArray();
            $allCategoryIds = array_merge([$catId], $childCategoryIds);
            $query->whereIn('category_id', $allCategoryIds);
        }

        // Filtering by Payment Method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filtering by Recurring status
        if ($request->filled('is_recurring')) {
            $query->where('is_recurring', (bool) $request->is_recurring);
        }

        // Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        // Amount Range
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        // Keyword Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%")
                  ->orWhere('payee_payer', 'like', "%$s%")
                  ->orWhere('reference_number', 'like', "%$s%")
                  ->orWhere('notes', 'like', "%$s%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $transactions = $query->latest('transaction_date')->latest('id')->paginate($perPage)->withQueryString();

        $accounts = $user->accounts()->where('is_active', true)->get();

        // Get hierarchical categories (parents with children)
        $parentCategories = Category::forUser($user->id)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) use ($user) {
                $q->forUser($user->id);
            }])
            ->get();

        return view('transactions.index', compact('transactions', 'accounts', 'parentCategories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'destination_account_id' => ['nullable', 'required_if:type,transfer', 'different:account_id', 'exists:accounts,id'],
            'category_id' => ['nullable', 'required_unless:type,transfer', 'exists:categories,id'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'payee_payer' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'in:bank_transfer,credit_card,debit_card,cash,upi,paypal,stripe,check,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_interval' => ['nullable', 'required_if:is_recurring,1', 'in:daily,weekly,monthly,quarterly,yearly'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'], // 5MB max
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts/' . $user->id, 'local');
        }

        DB::transaction(function () use ($user, $validated, $receiptPath) {
            $tx = $user->transactions()->create([
                'account_id' => $validated['account_id'],
                'destination_account_id' => $validated['destination_account_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'fee' => $validated['fee'] ?? 0,
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'payee_payer' => $validated['payee_payer'] ?? null,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'receipt_path' => $receiptPath,
                'notes' => $validated['notes'] ?? null,
                'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
                'recurrence_interval' => !empty($validated['is_recurring']) ? ($validated['recurrence_interval'] ?? 'monthly') : null,
                'status' => 'completed',
            ]);

            // Recalculate account balances
            $sourceAccount = Account::find($validated['account_id']);
            $sourceAccount->recalculateBalance();

            if (!empty($validated['destination_account_id'])) {
                $destAccount = Account::find($validated['destination_account_id']);
                $destAccount->recalculateBalance();
            }
        });

        return back()->with('success', 'Transaction successfully recorded!');
    }

    public function edit(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'destination_account_id' => ['nullable', 'required_if:type,transfer', 'different:account_id', 'exists:accounts,id'],
            'category_id' => ['nullable', 'required_unless:type,transfer', 'exists:categories,id'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'payee_payer' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'in:bank_transfer,credit_card,debit_card,cash,upi,paypal,stripe,check,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_interval' => ['nullable', 'in:daily,weekly,monthly,quarterly,yearly'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldSourceId = $transaction->account_id;
        $oldDestId = $transaction->destination_account_id;

        DB::transaction(function () use ($transaction, $validated, $request, $oldSourceId, $oldDestId) {
            $data = [
                'account_id' => $validated['account_id'],
                'destination_account_id' => $validated['destination_account_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'fee' => $validated['fee'] ?? 0,
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'payee_payer' => $validated['payee_payer'] ?? null,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
                'recurrence_interval' => !empty($validated['is_recurring']) ? ($validated['recurrence_interval'] ?? 'monthly') : null,
            ];

            if ($request->hasFile('receipt')) {
                // Delete old receipt if exists
                if ($transaction->receipt_path && Storage::disk('local')->exists($transaction->receipt_path)) {
                    Storage::disk('local')->delete($transaction->receipt_path);
                }
                $data['receipt_path'] = $request->file('receipt')->store('receipts/' . $transaction->user_id, 'local');
            }

            $transaction->update($data);

            // Recalculate affected accounts
            Account::find($oldSourceId)?->recalculateBalance();
            if ($oldDestId) {
                Account::find($oldDestId)?->recalculateBalance();
            }

            Account::find($validated['account_id'])?->recalculateBalance();
            if (!empty($validated['destination_account_id'])) {
                Account::find($validated['destination_account_id'])?->recalculateBalance();
            }
        });

        return back()->with('success', 'Transaction updated successfully!');
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        $sourceId = $transaction->account_id;
        $destId = $transaction->destination_account_id;
        $receiptPath = $transaction->receipt_path;

        DB::transaction(function () use ($transaction, $sourceId, $destId, $receiptPath) {
            if ($receiptPath && Storage::disk('local')->exists($receiptPath)) {
                Storage::disk('local')->delete($receiptPath);
            }

            $transaction->delete();

            Account::find($sourceId)?->recalculateBalance();
            if ($destId) {
                Account::find($destId)?->recalculateBalance();
            }
        });

        return back()->with('success', 'Transaction deleted successfully.');
    }

    public function downloadReceipt(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$transaction->receipt_path || !Storage::disk('local')->exists($transaction->receipt_path)) {
            abort(404, 'Receipt attachment not found.');
        }

        return Storage::disk('local')->response($transaction->receipt_path);
    }
}
