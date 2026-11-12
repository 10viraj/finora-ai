<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->transactions()->with(['account', 'category']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('month')) {
            $query->where('transaction_date', 'like', $request->month . '%');
        }

        $transactions = $query->latest('transaction_date')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'payee_payer' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string'],
        ]);

        $tx = DB::transaction(function () use ($user, $validated) {
            $transaction = $user->transactions()->create([
                'account_id' => $validated['account_id'],
                'category_id' => $validated['category_id'] ?? null,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'fee' => $validated['fee'] ?? 0,
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'payee_payer' => $validated['payee_payer'] ?? null,
                'payment_method' => $validated['payment_method'] ?? 'bank_transfer',
                'status' => 'completed',
            ]);

            $account = Account::find($validated['account_id']);
            $account->recalculateBalance();

            return $transaction;
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $tx->load(['account', 'category']),
        ], 201);
    }
}
