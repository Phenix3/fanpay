<?php

declare(strict_types=1);

namespace App\Domain\Users\Enums;

enum UserRole: string
{
    case Fan = 'fan';
    case Artist = 'artist';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Fan => __('messages.fan'),
            self::Artist => __('messages.artist'),
            self::Admin => __('messages.admin'),
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
