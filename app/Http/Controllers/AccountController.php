<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $accounts = $user->accounts()->withCount('transactions')->get();
        $totalBalance = $accounts->where('is_active', true)->sum('balance');

        return view('accounts.index', compact('accounts', 'totalBalance'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:checking,savings,credit_card,cash,business_checking,investment'],
            'account_number_last4' => ['nullable', 'string', 'size:4'],
            'initial_balance' => ['required', 'numeric'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
        ]);

        $account = $user->accounts()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'account_number_last4' => $validated['account_number_last4'] ?? null,
            'initial_balance' => $validated['initial_balance'],
            'balance' => $validated['initial_balance'],
            'currency' => $user->currency,
            'color' => $validated['color'] ?: '#2563eb',
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Account added successfully.');
    }

    public function toggleStatus(Account $account)
    {
        if ($account->user_id !== Auth::id()) {
            abort(403);
        }

        $account->update(['is_active' => !$account->is_active]);

        return back()->with('success', 'Account status updated.');
    }
}

