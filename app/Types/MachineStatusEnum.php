<?php

namespace App\Types;

enum MachineStatusEnum: string
{
    case ACTIVE = 'Active';
    case LOST_CONNECTION = 'Lost Connection';
    case RESOURCE_ALERT = 'Resource Alert';
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
