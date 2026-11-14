<?php

namespace App\Http\Controllers;

use App\Exports\TransactionsExport;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        $period = $request->input('period', 'monthly');
        if (!in_array($period, ['daily', 'weekly', 'monthly'])) {
            $period = 'monthly';
        }

        $selectedDate = $request->input('date', $now->format('Y-m-d'));
        $selectedWeek = $request->input('week', $now->format('Y-m-d'));
        $selectedMonth = $request->input('month', $now->format('Y-m'));

        $timeframe = match ($period) {
            'daily' => $selectedDate,
            'weekly' => $selectedWeek,
            default => $selectedMonth,
        };

        $activeTab = $request->input('tab', 'overview');

        // Fetch comprehensive report data
        $reportData = $this->reportService->getReportData($user, $period, $timeframe);

        return view('reports.index', array_merge($reportData, [
            'user' => $user,
            'activeTab' => $activeTab,
            'selectedDate' => $selectedDate,
            'selectedWeek' => $selectedWeek,
            'selectedMonth' => $selectedMonth,
            'largestExpense' => $reportData['largest_expense'],
        ]));
    }

    public function downloadPdf(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        $period = $request->input('period', 'monthly');
        if (!in_array($period, ['daily', 'weekly', 'monthly'])) {
            $period = 'monthly';
        }

        $timeframe = match ($period) {
            'daily' => $request->input('date', $now->format('Y-m-d')),
            'weekly' => $request->input('week', $now->format('Y-m-d')),
            default => $request->input('month', $now->format('Y-m')),
        };

        $reportData = $this->reportService->getReportData($user, $period, $timeframe);

        $pdf = Pdf::loadView('reports.pdf', array_merge($reportData, [
            'user' => $user,
        ]));

        $safeLabel = strtolower(str_replace([' ', '/', ','], '-', $reportData['label']));
        $safeDate = strtolower(str_replace([' ', '/', ','], '-', $timeframe));
        $filename = "financial-report-{$safeLabel}-{$safeDate}.pdf";

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        $period = $request->input('period', 'monthly');
        $format = $request->input('format', 'xlsx');
        if (!in_array($format, ['xlsx', 'csv'])) {
            $format = 'xlsx';
        }

        $timeframe = match ($period) {
            'daily' => $request->input('date', $now->format('Y-m-d')),
            'weekly' => $request->input('week', $now->format('Y-m-d')),
            default => $request->input('month', $now->format('Y-m')),
        };

        $reportData = $this->reportService->getReportData($user, $period, $timeframe);

        $filename = "transactions-{$period}-{$timeframe}.{$format}";

        return Excel::download(
            new TransactionsExport($user->id, $reportData['start_date'], $reportData['end_date']),
            $filename
        );
    }
}
