<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AiFinancialService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessAiFinancialAuditJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
    }

    public function handle(AiFinancialService $aiService): void
    {
        Log::info("Starting AI financial audit background job for User #{$this->user->id} ({$this->user->name})");

        $insights = $aiService->analyzeFinances($this->user);

        Log::info("AI financial audit completed for User #{$this->user->id}. Generated " . count($insights) . " new insights.");
    }
}

