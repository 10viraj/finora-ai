@extends('layouts.app')

@section('title', 'Notifications & Alerts - Finora AI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Notifications & System Alerts</h1>
            <p class="text-secondary small mb-0">Stay informed about budget thresholds, unusual spending, and account updates.</p>
        </div>
        @if(($unreadCount ?? 0) > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-check2-all me-1"></i> Mark All as Read
                </button>
            </form>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold">All Notifications</span>
            <span class="badge bg-primary-subtle text-primary">{{ $unreadCount ?? 0 }} Unread</span>
        </div>
        <div class="list-group list-group-flush">
            @forelse($notifications ?? [] as $notification)
                @php
                    $isUnread = empty($notification->read_at);
                    $icon = match($notification->type) {
                        'budget_alert' => 'bi-exclamation-triangle-fill text-warning',
                        'unusual_expense', 'spending_anomaly' => 'bi-shield-exclamation text-danger',
                        'goal_achieved' => 'bi-trophy-fill text-success',
                        default => 'bi-bell-fill text-primary',
                    };
                @endphp
                <div class="list-group-item p-3 {{ $isUnread ? 'bg-light bg-opacity-50' : '' }}">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="d-flex gap-3">
                            <div class="fs-4 mt-1">
                                <i class="{{ $icon }}"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="fw-bold mb-0 text-dark">{{ $notification->title }}</h6>
                                    @if($isUnread)
                                        <span class="badge bg-primary" style="font-size: 0.65rem;">NEW</span>
                                    @endif
                                </div>
                                <p class="text-secondary small mb-1 mt-1">{{ $notification->message }}</p>
                                <span class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-clock me-1"></i>{{ $notification->created_at ? $notification->created_at->diffForHumans() : 'Recently' }}
                                </span>
                            </div>
                        </div>
                        @if($isUnread)
                            <form action="{{ route('notifications.read', $notification) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm text-secondary text-decoration-none" title="Mark as Read">
                                    <i class="bi bi-check-lg fs-5"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-secondary">No notifications yet.</h5>
                    <p class="text-muted small">You're all caught up! New budget warnings and spending alerts will show up here.</p>
                </div>
            @endforelse
        </div>
        @if(method_exists($notifications, 'hasPages') && $notifications->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

