<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        $income = $user->getMonthlyIncome($selectedMonth);
        $expense = $user->getMonthlyExpenses($selectedMonth);
        $savings = $user->getMonthlySavings($selectedMonth);
        $savingsRate = $user->getSavingsRate($selectedMonth);

        $categorySpending = $user->transactions()
            ->selectRaw('categories.name, categories.color, SUM(transactions.amount) as total')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'expense')
            ->where('transactions.status', 'completed')
            ->where('transactions.transaction_date', 'like', "$selectedMonth%")
            ->groupBy('categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get();

        $budgets = $user->budgets()
            ->with('category')
            ->where(function ($q) use ($selectedMonth) {
                $q->whereNull('month_year')->orWhere('month_year', $selectedMonth);
            })
            ->get();

        return view('reports.index', compact(
            'selectedMonth',
            'income',
            'expense',
            'savings',
            'savingsRate',
            'categorySpending',
            'budgets',
            'user'
        ));
    }

    public function downloadPdf(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month', now()->format('Y-m'));

        $income = $user->getMonthlyIncome($month);
        $expense = $user->getMonthlyExpenses($month);
        $savings = $user->getMonthlySavings($month);
        $savingsRate = $user->getSavingsRate($month);

        $transactions = $user->transactions()
            ->with(['account', 'category'])
            ->where('transaction_date', 'like', "$month%")
            ->latest('transaction_date')
            ->get();

        $categorySpending = $user->transactions()
            ->selectRaw('categories.name, SUM(transactions.amount) as total')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('transactions.type', 'expense')
            ->where('transactions.status', 'completed')
            ->where('transactions.transaction_date', 'like', "$month%")
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        $monthName = Carbon::createFromFormat('Y-m', $month)->format('F Y');

        $pdf = Pdf::loadView('reports.pdf', compact(
            'user',
            'month',
            'monthName',
            'income',
            'expense',
            'savings',
            'savingsRate',
            'transactions',
            'categorySpending'
        ));

        return $pdf->download("financial-statement-{$month}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $month = $request->input('month');
        $format = $request->input('format', 'xlsx');

        $filename = $month
            ? "transactions-{$month}.{$format}"
            : "all-transactions-" . now()->format('Ymd') . ".{$format}";

        return Excel::download(new TransactionsExport($user->id, $month), $filename);
    }
}
