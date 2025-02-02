<?php

namespace App\Events;

enum EventGroupEnum: string
{
    case COASTER_CHECKER = 'coasterChecker';
    case COASTER_STATS = 'coasterStats';
    case COASTER_DATA = 'coasterData';
}
