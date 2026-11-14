<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AiFinancialService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateMonthlyInsights implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?User $user = null) {}

    /**
     * Execute the job.
     */
    public function handle(AiFinancialService $service): void
    {
        if ($this->user) {
            $service->analyzeFinances($this->user);
        } else {
            User::chunk(50, function ($users) use ($service) {
                foreach ($users as $u) {
                    $service->analyzeFinances($u);
                }
            });
        }
    }
}

