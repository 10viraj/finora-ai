<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected int $userId;
    protected ?string $startDate;
    protected ?string $endDate;
    protected ?string $monthYear;

    public function __construct(int $userId, ?string $startDateOrMonth = null, ?string $endDate = null)
    {
        $this->userId = $userId;

        // If second param provided, it's a date range
        if ($endDate !== null) {
            $this->startDate = $startDateOrMonth;
            $this->endDate = $endDate;
            $this->monthYear = null;
        } elseif ($startDateOrMonth !== null && strlen($startDateOrMonth) === 7) {
            // YYYY-MM
            $this->monthYear = $startDateOrMonth;
            $this->startDate = null;
            $this->endDate = null;
        } elseif ($startDateOrMonth !== null && strlen($startDateOrMonth) === 10) {
            // Single date YYYY-MM-DD
            $this->startDate = $startDateOrMonth;
            $this->endDate = $startDateOrMonth;
            $this->monthYear = null;
        } else {
            $this->monthYear = $startDateOrMonth;
            $this->startDate = null;
            $this->endDate = null;
        }
    }

    public function collection()
    {
        $query = Transaction::query()
            ->with(['account', 'category'])
            ->where('user_id', $this->userId)
            ->latest('transaction_date');

        if ($this->startDate && $this->endDate) {
            $query->whereDate('transaction_date', '>=', $this->startDate)
                  ->whereDate('transaction_date', '<=', $this->endDate);
        } elseif ($this->monthYear) {
            $query->where('transaction_date', 'like', "{$this->monthYear}%");
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Type',
            'Account',
            'Category',
            'Description',
            'Payee / Payer',
            'Amount',
            'Fee',
            'Payment Method',
            'Reference #',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->transaction_date ? $row->transaction_date->format('Y-m-d') : '',
            strtoupper($row->type),
            $row->account->name ?? 'N/A',
            $row->category->name ?? 'Uncategorized',
            $row->description,
            $row->payee_payer ?? '-',
            $row->amount,
            $row->fee,
            ucwords(str_replace('_', ' ', $row->payment_method)),
            $row->reference_number ?? '-',
            ucfirst($row->status),
        ];
    }
}
