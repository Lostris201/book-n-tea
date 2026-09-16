<?php

namespace App\Enums;

enum ReservationSource: string
{
    case Native = 'native';
    case External = 'external';
}
