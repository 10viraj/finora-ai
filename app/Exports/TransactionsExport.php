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
    protected ?string $monthYear;

    public function __construct(int $userId, ?string $monthYear = null)
    {
        $this->userId = $userId;
        $this->monthYear = $monthYear;
    }

    public function collection()
    {
        $query = Transaction::query()
            ->with(['account', 'category'])
            ->where('user_id', $this->userId)
            ->latest('transaction_date');

        if ($this->monthYear) {
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
