<?php

namespace App\Types;
//single source of type that can be used in throughout the application
enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 