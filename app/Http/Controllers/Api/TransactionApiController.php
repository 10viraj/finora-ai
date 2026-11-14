<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TransactionApiController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * List user transactions with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->transactions()->with(['account', 'category', 'paymentMethod', 'attachments']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->filled('month')) {
            $query->where('transaction_date', 'like', $request->month . '%');
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('payee_payer', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $transactions = $query->latest('transaction_date')->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'destination_account_id' => ['nullable', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'payment_method' => ['nullable', 'string'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'payee_payer' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'receipt' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
        ]);

        // Ownership check: Account must belong to the authenticated user
        $account = Account::withoutGlobalScopes()->where('id', $validated['account_id'])->first();
        if (!$account || $account->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized account access.',
            ], 403);
        }

        if (!empty($validated['destination_account_id'])) {
            $dest = Account::withoutGlobalScopes()->where('id', $validated['destination_account_id'])->first();
            if (!$dest || $dest->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized destination account access.',
                ], 403);
            }
        }

        $receipt = $request->file('receipt');
        $transaction = $this->transactionService->recordTransaction($user, $validated, $receipt);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction->load(['account', 'category', 'paymentMethod', 'attachments']),
        ], 201);
    }

    /**
     * View transaction details.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $transaction = Transaction::withoutGlobalScopes()->findOrFail($id);

        Gate::authorize('view', $transaction);

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['account', 'destinationAccount', 'category', 'paymentMethod', 'attachments']),
        ]);
    }

    /**
     * Update an existing transaction.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $transaction = Transaction::withoutGlobalScopes()->findOrFail($id);

        Gate::authorize('update', $transaction);

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'destination_account_id' => ['nullable', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'payment_method' => ['nullable', 'string'],
            'type' => ['required', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'payee_payer' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'receipt' => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
        ]);

        $receipt = $request->file('receipt');
        $updated = $this->transactionService->updateTransaction($transaction, $validated, $receipt);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully',
            'data' => $updated->load(['account', 'destinationAccount', 'category', 'paymentMethod', 'attachments']),
        ]);
    }

    /**
     * Soft delete transaction and recalculate balance.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $transaction = Transaction::withoutGlobalScopes()->findOrFail($id);

        Gate::authorize('delete', $transaction);

        $this->transactionService->deleteTransaction($transaction);

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ]);
    }

    /**
     * Get transaction summary metrics.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));

        $query = $user->transactions()
            ->where('status', 'completed')
            ->where('transaction_date', 'like', "{$month}%");

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        $income = (float) (clone $query)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $query)->where('type', 'expense')->sum('amount');
        $net = $income - $expense;
        $count = (clone $query)->count();

        $categorySpending = (clone $query)->where('type', 'expense')
            ->whereNotNull('category_id')
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'category_id' => $item->category_id,
                    'category_name' => $item->category?->name ?? 'Uncategorized',
                    'total' => (float) $item->total,
                ];
            });

        return response()->json([
            'success' => true,
            'month' => $month,
            'summary' => [
                'total_income' => $income,
                'total_expense' => $expense,
                'net_savings' => $net,
                'savings_rate' => $income > 0 ? round(($net / $income) * 100, 1) : 0,
                'transactions_count' => $count,
                'top_spending_categories' => $categorySpending,
            ],
        ]);
    }

    /**
     * Upload a receipt and extract amount, date, and merchant using OCR.
     */
    public function scanReceipt(Request $request, \App\Services\AI\ReceiptScannerService $scanner): JsonResponse
    {
        $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf,webp,txt', 'max:10240'],
        ]);

        $extracted = $scanner->scan($request->user(), $request->file('receipt'));

        return response()->json([
            'success' => true,
            'data' => $extracted,
        ]);
    }

    /**
     * Import existing transaction records via CSV.
     */
    public function importCsv(Request $request, \App\Services\TransactionCsvImportService $importer): JsonResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'account_id' => ['nullable', 'exists:accounts,id'],
        ]);

        $result = $importer->import($request->user(), $request->file('csv_file'), $request->account_id);

        return response()->json([
            'success' => $result['success'],
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'account_id' => $result['account_id'],
            'errors' => $result['errors'],
        ], $result['success'] ? 200 : 422);
    }
}
