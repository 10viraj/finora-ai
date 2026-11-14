@extends('layouts.app')

@section('title', 'Create Invoice')
@section('page-title', 'Create New Client Invoice')

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">
    <div class="card-custom">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-receipt-cutoff text-primary"></i>
                <span class="fw-bold">Invoice Details</span>
            </div>
            <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Invoices
            </a>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
                @csrf

                <!-- Basic Info -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Invoice Number *</label>
                        <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $nextNumber) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Issue Date *</label>
                        <input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Due Date *</label>
                        <input type="date" name="due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+15 days')) }}" required>
                    </div>
                </div>

                <!-- Client Info -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Client Name / Company *</label>
                        <input type="text" name="client_name" class="form-control" placeholder="e.g. Acme Corp" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Client Email</label>
                        <input type="email" name="client_email" class="form-control" placeholder="billing@client.com">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Client Billing Address</label>
                        <input type="text" name="client_address" class="form-control" placeholder="123 Business Way, Suite 100, City, Country">
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-secondary">Invoice Items</label>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th style="width: 120px;">Qty / Hours</th>
                                    <th style="width: 150px;">Unit Price ($)</th>
                                    <th style="width: 150px;">Total ($)</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr>
                                    <td>
                                        <input type="text" name="items[0][description]" class="form-control form-control-sm" placeholder="e.g. Full-stack Web Development" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.5" min="0.01" name="items[0][quantity]" class="form-control form-control-sm qty-input" value="1" required onchange="calculateTotals()">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[0][unit_price]" class="form-control form-control-sm price-input" value="1000.00" required onchange="calculateTotals()">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm row-total" value="$1,000.00" readonly>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeRow(this)" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRow()">
                        <i class="bi bi-plus-lg me-1"></i> Add Another Item
                    </button>
                </div>

                <!-- Calculations & Summary -->
                <div class="row justify-content-end mb-4">
                    <div class="col-md-5">
                        <div class="card bg-light border-0 p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Subtotal:</span>
                                <span class="fw-bold" id="displaySubtotal">$1,000.00</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small">Tax Rate (%):</span>
                                <input type="number" step="0.1" min="0" max="100" name="tax_rate" class="form-control form-control-sm text-end" style="width: 90px;" value="0" onchange="calculateTotals()">
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted small">Discount ($):</span>
                                <input type="number" step="0.01" min="0" name="discount_amount" class="form-control form-control-sm text-end" style="width: 90px;" value="0.00" onchange="calculateTotals()">
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold fs-6">Grand Total:</span>
                                <span class="fw-bold fs-5 text-primary" id="displayTotal">$1,000.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes / Terms -->
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Payment Terms & Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Payment due within 15 days via Wire/ACH. Thank you for your partnership!"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Save & Send Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let rowCount = 1;

function addRow() {
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="items[${rowCount}][description]" class="form-control form-control-sm" placeholder="Description" required></td>
        <td><input type="number" step="0.5" min="0.01" name="items[${rowCount}][quantity]" class="form-control form-control-sm qty-input" value="1" required onchange="calculateTotals()"></td>
        <td><input type="number" step="0.01" min="0" name="items[${rowCount}][unit_price]" class="form-control form-control-sm price-input" value="0.00" required onchange="calculateTotals()"></td>
        <td><input type="text" class="form-control form-control-sm row-total" value="$0.00" readonly></td>
        <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    rowCount++;
    calculateTotals();
}

function removeRow(btn) {
    const row = btn.closest('tr');
    row.remove();
    calculateTotals();
}

function calculateTotals() {
    const rows = document.querySelectorAll('#itemsBody tr');
    let subtotal = 0;

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        const lineTotal = qty * price;
        row.querySelector('.row-total').value = '$' + lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        subtotal += lineTotal;
    });

    const taxRate = parseFloat(document.querySelector('input[name="tax_rate"]').value) || 0;
    const discount = parseFloat(document.querySelector('input[name="discount_amount"]').value) || 0;

    const taxAmount = subtotal * (taxRate / 100);
    const grandTotal = Math.max(0, subtotal + taxAmount - discount);

    document.getElementById('displaySubtotal').innerText = '$' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('displayTotal').innerText = '$' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
@endsection

