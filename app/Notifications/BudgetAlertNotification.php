<?php

namespace App\Notifications;

use App\Models\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BudgetAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public Budget $budget, public float $percentage) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'budget_alert',
            'title' => "Budget Alert: {$this->budget->name}",
            'budget_id' => $this->budget->id,
            'budget_name' => $this->budget->name,
            'percentage' => $this->percentage,
            'message' => "Budget '{$this->budget->name}' has reached {$this->percentage}% of its allocated limit.",
        ];
    }
}

