<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogApiController extends Controller
{
    /**
     * View user's audit trail of important financial activity.
     */
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::where('user_id', $request->user()->id)
            ->latest('created_at')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $logs->count(),
            'data' => $logs,
        ]);
    }
}

