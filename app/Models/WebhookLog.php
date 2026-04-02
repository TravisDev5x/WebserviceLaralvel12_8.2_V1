<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class WebhookLog extends Model
{
    /* ----------------------------------------------
     *  Constantes de direccion
     * ---------------------------------------------- */
    public const DIRECTION_BOTMAKER_TO_BITRIX = 'botmaker_to_bitrix';
    public const DIRECTION_BITRIX_TO_BOTMAKER = 'bitrix_to_botmaker';

    /* ----------------------------------------------
     *  Constantes de estado
     * ---------------------------------------------- */
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    /* ----------------------------------------------
     *  Constantes de tipo de error
     * ---------------------------------------------- */
    public const ERROR_TIMEOUT = 'timeout';
    public const ERROR_VALIDATION = 'validation';
    public const ERROR_AUTH = 'auth';
    public const ERROR_SERVER = 'server_error';
    public const ERROR_UNKNOWN = 'unknown';

    protected $fillable = [
        'direction',
        'correlation_id',
        'external_id',
        'source_event',
        'payload_in',
        'payload_out',
        'http_status',
        'response_body',
        'status',
        'processing_ms',
        'error_message',
        'error_type',
        'source_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload_in' => 'array',
            'payload_out' => 'array',
            'http_status' => 'integer',
            'processing_ms' => 'integer',
        ];
    }

    /* ----------------------------------------------
     *  Boot: auto-generar correlation_id
     * ---------------------------------------------- */
    protected static function booted(): void
    {
        static::creating(function (WebhookLog $log): void {
            if (empty($log->correlation_id)) {
                $log->correlation_id = (string) Str::uuid();
            }
        });
    }

    /* ----------------------------------------------
     *  Relaciones
     * ---------------------------------------------- */
    public function failedWebhook(): HasOne
    {
        return $this->hasOne(FailedWebhook::class);
    }

    /* ----------------------------------------------
     *  Helpers de estado
     * ---------------------------------------------- */
    public function markAsProcessing(): self
    {
        $this->update(['status' => self::STATUS_PROCESSING]);

        return $this;
    }

    public function markAsSent(int $httpStatus, ?string $responseBody = null, ?int $processingMs = null): self
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'http_status' => $httpStatus,
            'response_body' => $responseBody,
            'processing_ms' => $processingMs,
        ]);

        return $this;
    }

    public function markAsFailed(string $errorMessage, string $errorType = self::ERROR_UNKNOWN, ?int $httpStatus = null): self
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'error_type' => $errorType,
            'http_status' => $httpStatus,
        ]);

        return $this;
    }

    /* ----------------------------------------------
     *  Scopes
     * ---------------------------------------------- */
    public function scopeDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeBotmakerToBitrix($query)
    {
        return $query->where('direction', self::DIRECTION_BOTMAKER_TO_BITRIX);
    }

    public function scopeBitrixToBotmaker($query)
    {
        return $query->where('direction', self::DIRECTION_BITRIX_TO_BOTMAKER);
    }

    /* ----------------------------------------------
     *  Factory estatico para crear log rapido
     * ---------------------------------------------- */
    public static function logIncoming(
        string $direction,
        string $sourceEvent,
        array $payloadIn,
        ?string $externalId = null,
        ?string $sourceIp = null,
        ?string $userAgent = null,
    ): self {
        return self::create([
            'direction' => $direction,
            'source_event' => $sourceEvent,
            'payload_in' => $payloadIn,
            'external_id' => $externalId,
            'source_ip' => $sourceIp,
            'user_agent' => $userAgent,
            'status' => self::STATUS_RECEIVED,
        ]);
    }
}
