<?php

namespace App\Enums;

enum WebhookStatus: string
{
    case Received = 'received';
    case Processing = 'processing';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recibido',
            self::Processing => 'Procesando',
            self::Sent => 'Enviado',
            self::Failed => 'Fallido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Received => 'info',
            self::Processing => 'warning',
            self::Sent => 'success',
            self::Failed => 'danger',
        };
    }
}
