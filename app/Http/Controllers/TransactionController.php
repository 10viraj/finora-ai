<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\BudgetService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected BudgetService $budgetService
    ) {}
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

    public function store(StoreTransactionRequest $request)
    {
        $user = Auth::user();

        $createdTx = $this->transactionService->recordTransaction(
            $user,
            $request->validated(),
            $request->file('receipt')
        );

        $response = back()->with('success', 'Transaction successfully recorded!');

        if ($createdTx && $createdTx->type === 'expense') {
            $budgetAlert = $this->budgetService->checkTransactionAlert($user, $createdTx);
            if ($budgetAlert) {
                $response->with('budget_alert', $budgetAlert);
            }
        }

        return $response;
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

        $this->transactionService->updateTransaction($transaction, $validated, $request->file('receipt'));

        $response = back()->with('success', 'Transaction updated successfully!');

        if ($transaction->type === 'expense') {
            $budgetAlert = $this->budgetService->checkTransactionAlert(Auth::user(), $transaction);
            if ($budgetAlert) {
                $response->with('budget_alert', $budgetAlert);
            }
        }

        return $response;
    }

    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }

        $this->transactionService->deleteTransaction($transaction);

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

    /**
     * Upload a receipt and extract amount, date, and merchant using OCR / text analysis.
     */
    public function scanReceipt(Request $request, \App\Services\AI\ReceiptScannerService $scanner)
    {
        $request->validate([
            'receipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $data = $scanner->scan(Auth::user(), $request->file('receipt'));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Import existing transaction records from a CSV file.
     */
    public function importCsv(Request $request, \App\Services\TransactionCsvImportService $importer)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'account_id' => ['nullable', 'exists:accounts,id'],
        ]);

        $result = $importer->import(Auth::user(), $request->file('csv_file'), $request->account_id);

        if (!$result['success']) {
            return back()->withErrors(['csv_file' => 'Import failed: ' . implode(', ', $result['errors'])]);
        }

        return back()->with('success', "CSV Import Complete: {$result['imported']} transactions imported, {$result['skipped']} duplicates/zero rows skipped.");
    }
}
