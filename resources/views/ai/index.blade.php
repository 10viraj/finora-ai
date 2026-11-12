@extends('layouts.app')

@section('title', 'AI Financial Advisor')
@section('page-title', 'AI Financial Advisor & Insights')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Audit Trigger Row -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-stars text-primary"></i> Intelligent Financial Advisor
            </h4>
            <p class="text-muted small mb-0">Automated spending anomaly detection, SaaS creep auditing, cashflow runway forecasts, and strategic savings tips.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border px-2 py-1">
                Model: {{ $user->ai_model ?: 'gpt-4o-mini' }}
            </span>
            <form action="{{ route('ai.generate') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                    <i class="bi bi-cpu me-1"></i> Run Financial Audit
                </button>
            </form>
        </div>
    </div>

    <!-- Interactive AI Assistant Query Box -->
    <div class="card-custom mb-4" style="border-left: 4px solid #3b82f6;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-chat-dots-fill"></i>
                </div>
                <h6 class="fw-bold mb-0 text-dark">Ask Your AI Financial Copilot</h6>
            </div>
            <p class="text-muted small mb-3">Ask questions about your burn rate, runway, upcoming tax provisions, or how to reduce discretionary expenditures.</p>

            <form id="aiAskForm" onsubmit="askAdvisor(event)">
                <div class="input-group">
                    <input type="text" id="aiQueryInput" class="form-control" placeholder="e.g. How many months of operational runway do I currently have?" required>
                    <button class="btn btn-primary px-4 fw-semibold" type="submit" id="aiSubmitBtn">
                        <i class="bi bi-send me-1"></i> Ask AI
                    </button>
                </div>
            </form>

            <!-- Quick Suggestions -->
            <div class="d-flex gap-2 mt-2 flex-wrap" style="font-size: 0.775rem;">
                <span class="text-muted">Suggested prompts:</span>
                <a href="javascript:void(0)" class="badge bg-light text-primary text-decoration-none border" onclick="setQuery('How long is my current cash runway based on daily burn?')">Runway calculation</a>
                <a href="javascript:void(0)" class="badge bg-light text-primary text-decoration-none border" onclick="setQuery('Where can I cut 15% from my operating expenses?')">Cut operating costs</a>
                <a href="javascript:void(0)" class="badge bg-light text-primary text-decoration-none border" onclick="setQuery('What is my current savings retention rate?')">Savings rate benchmark</a>
            </div>

            <!-- AI Response Box -->
            <div id="aiResponseCard" class="mt-3 p-3 bg-light rounded border" style="display: none;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-stars text-primary"></i>
                    <strong class="small text-dark" id="aiQuestionEcho"></strong>
                </div>
                <div class="small text-secondary" id="aiAnswerText" style="line-height: 1.6;"></div>
            </div>
        </div>
    </div>

    <!-- Active AI Insights Grid -->
    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-lightbulb text-warning"></i> Active Strategic Recommendations
        <span class="badge bg-primary rounded-pill small">{{ count($insights) }}</span>
    </h5>

    <div class="row g-3">
        @forelse($insights as $item)
            @php
                $typeIcons = [
                    'spending_anomaly' => 'bi-graph-up-arrow text-danger',
                    'budget_warning' => 'bi-exclamation-octagon text-warning',
                    'savings_opportunity' => 'bi-piggy-bank text-success',
                    'cashflow_forecast' => 'bi-calendar-check text-primary',
                    'business_advice' => 'bi-briefcase text-info',
                ];
                $priorityBadge = [
                    'high' => 'bg-danger',
                    'medium' => 'bg-warning text-dark',
                    'low' => 'bg-info text-dark',
                ];
            @endphp
            <div class="col-lg-6">
                <div class="card-custom h-100 d-flex flex-column justify-content-between">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi {{ $typeIcons[$item->type] ?? 'bi-stars' }} fs-4"></i>
                                <h6 class="fw-bold mb-0 text-dark">{{ $item->title }}</h6>
                            </div>
                            <span class="badge {{ $priorityBadge[$item->priority] ?? 'bg-secondary' }} px-2 py-1 text-uppercase" style="font-size: 0.65rem;">
                                {{ $item->priority }} Priority
                            </span>
                        </div>

                        <p class="text-secondary small mb-3">
                            {{ $item->summary }}
                        </p>

                        @if($item->detailed_analysis)
                            <div class="p-2 bg-light rounded small text-muted mb-3" style="font-size: 0.8rem; border-left: 3px solid #cbd5e1;">
                                {{ $item->detailed_analysis }}
                            </div>
                        @endif

                        @if($item->action_suggested)
                            <div class="d-flex align-items-start gap-2 text-primary small fw-semibold">
                                <i class="bi bi-arrow-right-circle-fill mt-1"></i>
                                <span>Suggested Action: {{ $item->action_suggested }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="card-footer bg-white border-top py-2 px-3 d-flex align-items-center justify-content-between text-muted small">
                        <span>Generated {{ $item->created_at->diffForHumans() }}</span>
                        <form action="{{ route('ai.dismiss', $item) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-link btn-sm text-muted p-0 text-decoration-none">
                                <i class="bi bi-check2 me-1"></i> Dismiss
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card-custom text-center py-5">
                    <i class="bi bi-check2-circle fs-1 text-success mb-2"></i>
                    <h5>All Financial Signals Clear</h5>
                    <p class="text-muted small mb-3">No active budget anomalies or risk thresholds triggered. Click "Run Financial Audit" anytime to refresh your metrics.</p>
                    <form action="{{ route('ai.generate') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-cpu me-1"></i> Run Financial Audit
                        </button>
                    </form>
                </div>
            </div>
        @endforelse
    </div>

</div>

<script>
function setQuery(text) {
    document.getElementById('aiQueryInput').value = text;
}

function askAdvisor(e) {
    e.preventDefault();
    const queryInput = document.getElementById('aiQueryInput');
    const query = queryInput.value.trim();
    if (!query) return;

    const btn = document.getElementById('aiSubmitBtn');
    const responseCard = document.getElementById('aiResponseCard');
    const questionEcho = document.getElementById('aiQuestionEcho');
    const answerText = document.getElementById('aiAnswerText');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Analyzing...';

    fetch('{{ route('ai.ask') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ query: query })
    })
    .then(res => res.json())
    .then(data => {
        questionEcho.innerText = 'Q: ' + data.question;
        answerText.innerText = data.answer;
        responseCard.style.display = 'block';
    })
    .catch(err => {
        alert('An error occurred while consulting the AI financial service.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send me-1"></i> Ask AI';
    });
}
</script>
@endsection
