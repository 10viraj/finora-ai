@extends('layouts.app')

@section('title', 'Categories - FinTrack AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Income & Expense Categories</h1>
            <p class="text-secondary small mb-0">Organize and classify all financial transactions and budget allocations.</p>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newCategoryModal">
            <i class="bi bi-plus-lg me-1"></i> Add Category
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Expense Categories -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <span class="badge bg-danger-subtle text-danger me-2"><i class="bi bi-arrow-up-right"></i></span>
                    <h5 class="card-title mb-0 fw-bold">Expense Categories</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($categories->where('type', 'expense') as $category)
                            <div class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                             style="width: 40px; height: 40px; background-color: {{ $category->color }}20; color: {{ $category->color }};">
                                            <i class="bi {{ $category->icon ?: 'bi-tag' }} fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $category->name }}</div>
                                            <span class="badge bg-light text-secondary border small">
                                                {{ $category->is_default ? 'System Default' : 'Custom Category' }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $category->children->count() }} subcategories</span>
                                </div>
                                @if($category->children->isNotEmpty())
                                    <div class="ms-5 mt-2 ps-2 border-start">
                                        @foreach($category->children as $child)
                                            <div class="d-flex align-items-center justify-content-between py-1">
                                                <span class="small text-secondary"><i class="bi bi-arrow-return-right me-1"></i> {{ $child->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">No expense categories found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Income Categories -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
                    <span class="badge bg-success-subtle text-success me-2"><i class="bi bi-arrow-down-left"></i></span>
                    <h5 class="card-title mb-0 fw-bold">Income Categories</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($categories->where('type', 'income') as $category)
                            <div class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                             style="width: 40px; height: 40px; background-color: {{ $category->color }}20; color: {{ $category->color }};">
                                            <i class="bi {{ $category->icon ?: 'bi-cash' }} fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $category->name }}</div>
                                            <span class="badge bg-light text-secondary border small">
                                                {{ $category->is_default ? 'System Default' : 'Custom Category' }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $category->children->count() }} subcategories</span>
                                </div>
                                @if($category->children->isNotEmpty())
                                    <div class="ms-5 mt-2 ps-2 border-start">
                                        @foreach($category->children as $child)
                                            <div class="d-flex align-items-center justify-content-between py-1">
                                                <span class="small text-secondary"><i class="bi bi-arrow-return-right me-1"></i> {{ $child->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-4 text-center text-muted">No income categories found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Category -->
<div class="modal fade" id="newCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('categories.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Health & Wellness">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Type *</label>
                    <select name="type" class="form-select" required>
                        <option value="expense" selected>Expense</option>
                        <option value="income">Income</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Icon Class</label>
                    <input type="text" name="icon" class="form-control" value="bi-tag" placeholder="bi-tag">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Color Hex</label>
                    <input type="color" name="color" class="form-control form-control-color" value="#2563eb">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Category</button>
            </div>
        </form>
    </div>
</div>
@endsection

