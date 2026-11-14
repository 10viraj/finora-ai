@extends('layouts.app')

@section('title', 'Invoices')
@section('page-title', 'Client Invoices & Receivables')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Action Row -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Invoices & Billing</h4>
            <p class="text-muted small mb-0">Generate professional PDF invoices, track receivables, and log incoming client revenue.</p>
        </div>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Create Invoice
        </a>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Pending Receivables</div>
                <h3 class="fw-bold text-primary mt-2 mb-0">${{ number_format($totalReceivables, 2) }}</h3>
                <div class="text-muted small mt-2">Sent & Overdue invoices</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Total Collected</div>
                <h3 class="fw-bold text-success mt-2 mb-0">${{ number_format($totalPaid, 2) }}</h3>
                <div class="text-muted small mt-2">All-time settled invoices</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="text-muted small fw-semibold text-uppercase">Total Invoices</div>
                <h3 class="fw-bold text-dark mt-2 mb-0">{{ $invoices->total() }}</h3>
                <div class="text-muted small mt-2">Active billing records</div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card-custom">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-receipt text-primary"></i>
                <span class="fw-bold">All Invoices</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-outline-secondary {{ !request('status') ? 'active' : '' }}">All</a>
                <a href="{{ route('invoices.index', ['status' => 'sent']) }}" class="btn btn-sm btn-outline-primary {{ request('status') === 'sent' ? 'active' : '' }}">Sent</a>
                <a href="{{ route('invoices.index', ['status' => 'paid']) }}" class="btn btn-sm btn-outline-success {{ request('status') === 'paid' ? 'active' : '' }}">Paid</a>
                <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="btn btn-sm btn-outline-danger {{ request('status') === 'overdue' ? 'active' : '' }}">Overdue</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Invoice #</th>
                            <th>Client</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                            @php
                                $statusBadges = [
                                    'draft' => 'bg-secondary',
                                    'sent' => 'bg-primary',
                                    'paid' => 'bg-success',
                                    'overdue' => 'bg-danger',
                                    'cancelled' => 'bg-dark',
                                ];
                            @endphp
                            <tr>
                                <td class="ps-3 fw-bold text-dark">{{ $inv->invoice_number }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $inv->client_name }}</div>
                                    @if($inv->client_email)
                                        <div class="text-muted small">{{ $inv->client_email }}</div>
                                    @endif
                                </td>
                                <td class="small text-secondary">{{ $inv->issue_date->format('M d, Y') }}</td>
                                <td class="small text-secondary">{{ $inv->due_date->format('M d, Y') }}</td>
                                <td class="fw-bold text-dark">${{ number_format($inv->total_amount, 2) }}</td>
                                <td>
                                    <span class="badge {{ $statusBadges[$inv->status] ?? 'bg-secondary' }} px-2 py-1 text-uppercase" style="font-size: 0.65rem;">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <a href="{{ route('invoices.pdf', $inv) }}" class="btn btn-sm btn-outline-secondary" title="Download PDF">
                                            <i class="bi bi-file-earmark-pdf text-danger"></i> PDF
                                        </a>

                                        <!-- Status dropdown -->
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Status
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <form action="{{ route('invoices.status', $inv) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="paid">
                                                        <button type="submit" class="dropdown-item text-success"><i class="bi bi-check2-circle me-1"></i> Mark as Paid</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('invoices.status', $inv) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="sent">
                                                        <button type="submit" class="dropdown-item text-primary"><i class="bi bi-send me-1"></i> Mark as Sent</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('invoices.status', $inv) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="overdue">
                                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-exclamation-circle me-1"></i> Mark as Overdue</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                                    No invoices created yet.
                                    <div class="mt-2">
                                        <a href="{{ route('invoices.create') }}" class="btn btn-sm btn-primary">Create Your First Invoice</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $invoices->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>
@endsection

