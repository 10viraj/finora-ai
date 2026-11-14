<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    /**
     * List user's budget and anomaly alerts.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->take(30)->get();

        return response()->json([
            'success' => true,
            'count' => $notifications->count(),
            'unread_count' => $notifications->whereNull('read_at')->count(),
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->update(['read_at' => now(), 'is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }
}

