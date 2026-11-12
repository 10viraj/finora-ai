<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Services\AiFinancialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAdvisorController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $insights = $user->aiInsights()->where('is_dismissed', false)->get();
        $dismissedInsights = $user->aiInsights()->where('is_dismissed', true)->take(5)->get();

        return view('ai.index', compact('insights', 'dismissedInsights', 'user'));
    }

    public function generate(AiFinancialService $aiService)
    {
        $user = Auth::user();
        $aiService->analyzeFinances($user);

        return back()->with('success', 'AI financial audit completed! New insights and recommendations have been generated.');
    }

    public function ask(Request $request, AiFinancialService $aiService)
    {
        $request->validate([
            'query' => ['required', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $answer = $aiService->askAdvisor($user, $request->input('query'));

        return response()->json([
            'success' => true,
            'question' => $request->input('query'),
            'answer' => $answer,
        ]);
    }

    public function dismiss(AiInsight $insight)
    {
        if ($insight->user_id !== Auth::id()) {
            abort(403);
        }

        $insight->update(['is_dismissed' => true]);

        return back()->with('success', 'Insight dismissed.');
    }
}
