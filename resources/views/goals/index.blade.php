@extends('layouts.app')

@section('title', 'Financial & Savings Goals - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Financial & Savings Goals</h1>
            <p class="text-secondary small mb-0">Track progress towards long-term targets, emergency funds, and capital milestones.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newGoalModal">
                <i class="bi bi-plus-lg me-1"></i> New Savings Goal
            </button>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Target</div>
                        <div class="fs-4 fw-bold">₹{{ number_format($totalTarget ?? 0, 2) }}</div>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="bi bi-bullseye fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Saved</div>
                        <div class="fs-4 fw-bold text-success">₹{{ number_format($totalSaved ?? 0, 2) }}</div>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="bi bi-piggy-bank fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Overall Pace</div>
                        <div class="fs-4 fw-bold text-primary">{{ $overallProgress ?? 0 }}%</div>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="bi bi-graph-up-arrow fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Goals Grid -->
    <div class="row g-4">
        @forelse($goals ?? [] as $goal)
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary-subtle text-primary text-uppercase">{{ $goal->category }}</span>
                            <span class="badge {{ $goal->status === 'completed' ? 'bg-success text-white' : 'bg-light text-secondary border' }}">{{ ucfirst(str_replace('_', ' ', $goal->status)) }}</span>
                        </div>
                        <h5 class="fw-bold mb-2">{{ $goal->name }}</h5>
                        <p class="text-secondary small mb-4 flex-grow-1">{{ $goal->notes ?: 'No additional notes provided.' }}</p>

                        <div class="d-flex justify-content-between small fw-semibold mb-1">
                            <span>Saved: ₹{{ number_format($goal->current_amount, 2) }}</span>
                            <span>Target: ₹{{ number_format($goal->target_amount, 2) }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar {{ $goal->progress_percentage >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ min(100, $goal->progress_percentage) }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted small mb-3">
                            <span>Progress: {{ $goal->progress_percentage }}%</span>
                            <span>Target: {{ $goal->target_date ? $goal->target_date->format('M d, Y') : 'Ongoing' }}</span>
                        </div>

                        <hr class="text-muted my-2">

                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <!-- Quick Deposit Form -->
                            <form action="{{ route('goals.deposit', $goal) }}" method="POST" class="d-flex gap-1 flex-grow-1 me-2">
                                @csrf
                                <input type="number" step="0.01" min="1" name="amount" class="form-control form-control-sm" placeholder="₹ Add amount" required>
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-plus"></i> Save
                                </button>
                            </form>

                            <form action="{{ route('goals.destroy', $goal) }}" method="POST" onsubmit="return confirm('Delete this financial goal?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="bi bi-bullseye fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-secondary">No financial goals set yet.</h5>
                <p class="text-muted small">Set savings milestones and emergency fund goals to track with AI.</p>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newGoalModal">
                    Create First Goal
                </button>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal: New Goal -->
<div class="modal fade" id="newGoalModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('goals.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Create Savings Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Goal Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Emergency Fund (6 Months)" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Target Amount (₹)</label>
                        <input type="number" step="0.01" min="1" name="target_amount" class="form-control" placeholder="50000" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Initial Saved (₹)</label>
                        <input type="number" step="0.01" min="0" name="current_amount" class="form-control" placeholder="0">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Target Date</label>
                        <input type="date" name="target_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="savings">General Savings</option>
                            <option value="emergency_fund">Emergency Fund</option>
                            <option value="debt_payoff">Debt Payoff</option>
                            <option value="equipment">Equipment / Tech</option>
                            <option value="travel">Travel & Holiday</option>
                            <option value="investment">Long-Term Investment</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Notes & Strategy</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Goal</button>
            </div>
        </form>
    </div>
</div>
@endsection
