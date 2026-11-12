<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->transactions()->with(['account', 'destinationAccount', 'category']);

        // Filtering
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%")
                  ->orWhere('payee_payer', 'like', "%$s%")
                  ->orWhere('reference_number', 'like', "%$s%");
            });
        }

        $transactions = $query->latest('transaction_date')->latest('id')->paginate(15)->withQueryString();

        $accounts = $user->accounts()->where('is_active', true)->get();
        $categories = Category::forUser($user->id)->get();

        return view('transactions.index', compact('transactions', 'accounts', 'categories'));
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
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($user, $validated) {
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
                'notes' => $validated['notes'] ?? null,
                'status' => 'completed',
            ]);

            // Recalculate source account balance
            $sourceAccount = Account::find($validated['account_id']);
            $sourceAccount->recalculateBalance();

            // If transfer, recalculate destination account balance
            if (!empty($validated['destination_account_id'])) {
                $destAccount = Account::find($validated['destination_account_id']);
                $destAccount->recalculateBalance();
            }
        });

        return back()->with('success', 'Transaction recorded successfully!');
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        $sourceId = $transaction->account_id;
        $destId = $transaction->destination_account_id;

        DB::transaction(function () use ($transaction, $sourceId, $destId) {
            $transaction->delete();

            $sourceAccount = Account::find($sourceId);
            if ($sourceAccount) {
                $sourceAccount->recalculateBalance();
            }

            if ($destId) {
                $destAccount = Account::find($destId);
                if ($destAccount) {
                    $destAccount->recalculateBalance();
                }
            }
        });

        return back()->with('success', 'Transaction removed.');
    }
}
