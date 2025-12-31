<?php

namespace App\Enum;

enum NotificationType: string
{
    case PRICE_DECREASE = 'price_decrease';
    case PRICE_INCREASE = 'price_increase';
    case SITE_BROKEN = 'site_broken';

    public function label(): string
    {
        return match ($this) {
            self::PRICE_DECREASE => 'Prijsdaling',
            self::PRICE_INCREASE => 'Prijsstijging',
            self::SITE_BROKEN => 'Site onbereikbaar',
        };
    }
}
