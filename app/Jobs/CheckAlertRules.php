<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AlertRule;
use App\Models\FailedWebhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class CheckAlertRules implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $rules = AlertRule::query()->where('is_active', true)->get();

        foreach ($rules as $rule) {
            if ($rule->last_triggered_at && $rule->last_triggered_at->gt(now()->subMinutes($rule->cooldown_minutes))) {
                continue;
            }

            $count = $this->evaluateRule((string) $rule->condition_type, (int) $rule->time_window_minutes);
            if ($count < (int) $rule->threshold) {
                continue;
            }

            Mail::raw(
                "Alerta '{$rule->name}': se detectaron {$count} eventos en {$rule->time_window_minutes} minutos.",
                static function ($message) use ($rule): void {
                    $message->to($rule->notify_email)->subject('Alerta del monitor de webhooks');
                },
            );

            $rule->last_triggered_at = now();
            $rule->save();
        }
    }

    private function evaluateRule(string $type, int $window): int
    {
        $start = now()->subMinutes(max(1, $window));

        return match ($type) {
            'failed_webhooks' => FailedWebhook::query()->where('created_at', '>=', $start)->count(),
            'webhook_errors' => WebhookLog::query()->where('created_at', '>=', $start)->where('status', 'failed')->count(),
            'queue_stuck' => WebhookLog::query()->where('created_at', '<', now()->subMinutes(10))->whereIn('status', ['received', 'processing'])->count(),
            default => 0,
        };
    }
}
