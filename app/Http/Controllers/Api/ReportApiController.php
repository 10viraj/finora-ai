<?php

namespace App\Http\Controllers\Api;

use App\Exports\TransactionsExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportApiController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Monthly Report API.
     */
    public function monthly(Request $request): JsonResponse
    {
        $user = $request->user();
        $month = $request->input('month', now()->format('Y-m'));

        $data = $this->reportService->getReportData($user, 'monthly', $month);

        return response()->json([
            'success' => true,
            'period' => 'monthly',
            'timeframe' => $month,
            'label' => $data['label'],
            'formatted_date' => $data['formatted_date'],
            'summary' => [
                'income' => $data['income'],
                'expense' => $data['expense'],
                'savings' => $data['savings'],
                'savings_rate' => $data['savings_rate'],
                'daily_avg_spend' => $data['daily_avg_spend'],
                'transactions_count' => $data['transactions_count'],
            ],
            'category_breakdown' => $data['category_breakdown'],
            'trends' => $data['trend_data'],
            'savings_analysis' => $data['savings_analysis'],
        ]);
    }

    /**
     * Yearly / Annual Report API.
     */
    public function yearly(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = $request->input('year', now()->format('Y'));

        $data = $this->reportService->getReportData($user, 'yearly', $year);

        return response()->json([
            'success' => true,
            'period' => 'yearly',
            'timeframe' => $year,
            'label' => $data['label'],
            'formatted_date' => $data['formatted_date'],
            'summary' => [
                'income' => $data['income'],
                'expense' => $data['expense'],
                'savings' => $data['savings'],
                'savings_rate' => $data['savings_rate'],
                'daily_avg_spend' => $data['daily_avg_spend'],
                'transactions_count' => $data['transactions_count'],
            ],
            'category_breakdown' => $data['category_breakdown'],
            'trends' => $data['trend_data'],
            'savings_analysis' => $data['savings_analysis'],
        ]);
    }

    /**
     * Category Report API.
     */
    public function categories(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->input('period', 'monthly');
        $now = Carbon::now();

        $timeframe = match ($period) {
            'daily' => $request->input('date', $now->format('Y-m-d')),
            'weekly' => $request->input('week', $now->format('Y-m-d')),
            'yearly' => $request->input('year', $now->format('Y')),
            default => $request->input('month', $now->format('Y-m')),
        };

        $data = $this->reportService->getReportData($user, $period, $timeframe);

        return response()->json([
            'success' => true,
            'period' => $period,
            'timeframe' => $timeframe,
            'total_expense' => $data['expense'],
            'categories' => $data['category_breakdown'],
            'category_expenses' => $data['category_breakdown'],
        ]);
    }

    /**
     * Download PDF Financial Report.
     */
    public function exportPdf(Request $request): Response
    {
        $user = $request->user();
        $now = Carbon::now();

        $period = $request->input('period', 'monthly');
        $timeframe = match ($period) {
            'daily' => $request->input('date', $now->format('Y-m-d')),
            'weekly' => $request->input('week', $now->format('Y-m-d')),
            'yearly' => $request->input('year', $now->format('Y')),
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

    /**
     * Download Excel/CSV Financial Report.
     */
    public function exportExcel(Request $request): Response
    {
        $user = $request->user();
        $now = Carbon::now();

        $period = $request->input('period', 'monthly');
        $format = $request->input('format', 'xlsx');
        if (!in_array($format, ['xlsx', 'csv'])) {
            $format = 'xlsx';
        }

        $timeframe = match ($period) {
            'daily' => $request->input('date', $now->format('Y-m-d')),
            'weekly' => $request->input('week', $now->format('Y-m-d')),
            'yearly' => $request->input('year', $now->format('Y')),
            default => $request->input('month', $now->format('Y-m')),
        };

        $reportData = $this->reportService->getReportData($user, $period, $timeframe);

        $startDate = $reportData['start_date'];
        $endDate = $reportData['end_date'];

        $safeLabel = strtolower(str_replace([' ', '/', ','], '-', $reportData['label']));
        $safeDate = strtolower(str_replace([' ', '/', ','], '-', $timeframe));
        $filename = "transactions-{$safeLabel}-{$safeDate}.{$format}";

        $excelFormat = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download(new TransactionsExport($user->id, $startDate, $endDate), $filename, $excelFormat);
    }

    /**
     * Backward-compatible summary endpoint.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->input('period', 'monthly');
        $now = Carbon::now();

        $timeframe = match ($period) {
            'daily' => $request->input('date', $now->format('Y-m-d')),
            'weekly' => $request->input('week', $now->format('Y-m-d')),
            'yearly' => $request->input('year', $now->format('Y')),
            default => $request->input('month', $now->format('Y-m')),
        };

        $data = $this->reportService->getReportData($user, $period, $timeframe);

        return response()->json([
            'success' => true,
            'period' => $period,
            'timeframe' => $timeframe,
            'label' => $data['label'],
            'formatted_date' => $data['formatted_date'],
            'summary' => [
                'income' => $data['income'],
                'expense' => $data['expense'],
                'savings' => $data['savings'],
                'savings_rate' => $data['savings_rate'],
                'daily_avg_spend' => $data['daily_avg_spend'],
                'transactions_count' => $data['transactions_count'],
            ],
            'savings_analysis' => $data['savings_analysis'],
        ]);
    }

    /**
     * Backward-compatible trends endpoint.
     */
    public function trends(Request $request): JsonResponse
    {
        $user = $request->user();
        $period = $request->input('period', 'monthly');
        $now = Carbon::now();

        $timeframe = match ($period) {
            'daily' => $request->input('date', $now->format('Y-m-d')),
            'weekly' => $request->input('week', $now->format('Y-m-d')),
            'yearly' => $request->input('year', $now->format('Y')),
            default => $request->input('month', $now->format('Y-m')),
        };

        $data = $this->reportService->getReportData($user, $period, $timeframe);

        return response()->json([
            'success' => true,
            'period' => $period,
            'trends' => $data['trend_data'],
        ]);
    }

    /**
     * Backward-compatible category expenses endpoint.
     */
    public function categoryExpenses(Request $request): JsonResponse
    {
        return $this->categories($request);
    }
}
