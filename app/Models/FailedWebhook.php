<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class FailedWebhook extends Model
{
    /* ----------------------------------------------
     *  Constantes de estado
     * ---------------------------------------------- */
    public const STATUS_PENDING = 'pending';

    public const STATUS_RETRYING = 'retrying';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_EXHAUSTED = 'exhausted';

    /* ----------------------------------------------
     *  Schedule de backoff por defecto (en segundos)
     *  30s -> 1m -> 5m -> 15m -> 1h
     * ---------------------------------------------- */
    public const DEFAULT_BACKOFF = [30, 60, 300, 900, 3600];

    protected $fillable = [
        'webhook_log_id',
        'direction',
        'payload',
        'target_url',
        'attempts',
        'max_attempts',
        'backoff_schedule',
        'next_retry_at',
        'last_error',
        'last_http_status',
        'resolved_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'backoff_schedule' => 'array',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'last_http_status' => 'integer',
            'next_retry_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /* ----------------------------------------------
     *  Relaciones
     * ---------------------------------------------- */
    public function webhookLog(): BelongsTo
    {
        return $this->belongsTo(WebhookLog::class);
    }

    /* ----------------------------------------------
     *  Helpers de ciclo de vida
     * ---------------------------------------------- */

    /**
     * Crear un registro de fallo a partir de un WebhookLog.
     */
    public static function createFromLog(
        WebhookLog $log,
        array $payload,
        ?string $targetUrl = null,
        ?string $error = null,
        ?int $httpStatus = null,
    ): self {
        $maxAttempts = (int) config_dynamic('retry.max_attempts', 5);
        $backoffSchedule = config_dynamic('retry.backoff_schedule', self::DEFAULT_BACKOFF);
        $schedule = is_array($backoffSchedule) && $backoffSchedule !== [] ? array_values(array_map('intval', $backoffSchedule)) : self::DEFAULT_BACKOFF;

        return self::create([
            'webhook_log_id' => $log->id,
            'direction' => $log->direction,
            'payload' => $payload,
            'target_url' => $targetUrl,
            'attempts' => 1,
            'max_attempts' => max(1, $maxAttempts),
            'backoff_schedule' => $schedule,
            'next_retry_at' => now()->addSeconds((int) ($schedule[0] ?? self::DEFAULT_BACKOFF[0])),
            'last_error' => $error,
            'last_http_status' => $httpStatus,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Registrar un intento fallido y calcular proximo reintento.
     */
    public function recordFailedAttempt(string $error, ?int $httpStatus = null): self
    {
        $this->attempts++;
        $this->last_error = $error;
        $this->last_http_status = $httpStatus;

        if ($this->attempts >= $this->max_attempts) {
            $this->status = self::STATUS_EXHAUSTED;
            $this->next_retry_at = null;
        } else {
            $schedule = $this->backoff_schedule ?? self::DEFAULT_BACKOFF;
            $delay = $schedule[$this->attempts - 1] ?? end($schedule);
            $this->status = self::STATUS_PENDING;
            $this->next_retry_at = now()->addSeconds($delay);
        }

        $this->save();

        return $this;
    }

    /**
     * Marcar como resuelto tras envio exitoso.
     */
    public function markAsResolved(): self
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'next_retry_at' => null,
        ]);

        return $this;
    }

    /**
     * Se agotaron los reintentos?
     */
    public function isExhausted(): bool
    {
        return $this->status === self::STATUS_EXHAUSTED;
    }

    /**
     * Esta listo para reintentar?
     */
    public function isReadyForRetry(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->next_retry_at !== null
            && $this->next_retry_at->lte(now());
    }

    /* ----------------------------------------------
     *  Scopes
     * ---------------------------------------------- */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReadyForRetry($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now());
    }

    public function scopeExhausted($query)
    {
        return $query->where('status', self::STATUS_EXHAUSTED);
    }

    protected static function booted(): void
    {
        static::saved(static function (): void {
            Cache::forget('sidebar_failed_pending_v1');
        });
        static::deleted(static function (): void {
            Cache::forget('sidebar_failed_pending_v1');
        });
    }
}
