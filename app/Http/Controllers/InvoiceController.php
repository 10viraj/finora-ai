<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->invoices()->with('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->latest('issue_date')->paginate(10)->withQueryString();

        $totalReceivables = $user->invoices()->whereIn('status', ['sent', 'overdue'])->sum('total_amount');
        $totalPaid = $user->invoices()->where('status', 'paid')->sum('total_amount');

        return view('invoices.index', compact('invoices', 'totalReceivables', 'totalPaid'));
    }

    public function create()
    {
        $user = Auth::user();
        $nextNumber = 'INV-' . date('Y') . '-' . str_pad($user->invoices()->count() + 1, 3, '0', STR_PAD_LEFT);

        return view('invoices.create', compact('nextNumber', 'user'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'invoice_number' => ['required', 'string', 'max:50', 'unique:invoices,invoice_number'],
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_address' => ['nullable', 'string'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += ($item['quantity'] * $item['unit_price']);
            }

            $taxRate = (float) $validated['tax_rate'];
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $discount = (float) ($validated['discount_amount'] ?? 0);
            $totalAmount = max(0, $subtotal + $taxAmount - $discount);

            $invoice = $user->invoices()->create([
                'invoice_number' => $validated['invoice_number'],
                'client_name' => $validated['client_name'],
                'client_email' => $validated['client_email'] ?? null,
                'client_address' => $validated['client_address'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discount,
                'total_amount' => $totalAmount,
                'currency' => $user->currency,
                'status' => 'sent',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $lineTotal = round($item['quantity'] * $item['unit_price'], 2);
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $lineTotal,
                ]);
            }
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice generated successfully!');
    }

    public function downloadPdf(Invoice $invoice)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        $invoice->load(['user', 'items']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function updateStatus(Request $request, Invoice $invoice)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,paid,overdue,cancelled'],
        ]);

        $invoice->update(['status' => $validated['status']]);

        return back()->with('success', 'Invoice status updated.');
    }
}
