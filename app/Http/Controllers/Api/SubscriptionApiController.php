<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionApiController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    /**
     * View subscription plans, current tier, and daily AI quotas.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $plans = $this->subscriptionService->getAvailablePlans();
        $current = $this->subscriptionService->getUserSubscription($user);

        return response()->json([
            'success' => true,
            'data' => [
                'current_subscription' => $current,
                'available_plans' => $plans,
            ],
        ]);
    }

    /**
     * Upgrade subscription plan (e.g. to pro or enterprise).
     */
    public function upgrade(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'in:free,pro,enterprise'],
        ]);

        $user = $this->subscriptionService->upgradePlan($request->user(), $validated['plan']);
        $current = $this->subscriptionService->getUserSubscription($user);

        return response()->json([
            'success' => true,
            'message' => "Successfully upgraded to {$current['plan_name']}.",
            'data' => $current,
        ]);
    }
}

