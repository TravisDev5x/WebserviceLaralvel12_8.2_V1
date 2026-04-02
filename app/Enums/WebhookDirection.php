<?php

namespace App\Enums;

enum WebhookDirection: string
{
    case BotmakerToBitrix = 'botmaker_to_bitrix';
    case BitrixToBotmaker = 'bitrix_to_botmaker';

    public function label(): string
    {
        return match ($this) {
            self::BotmakerToBitrix => 'Botmaker -> Bitrix24',
            self::BitrixToBotmaker => 'Bitrix24 -> Botmaker',
        };
    }
}
