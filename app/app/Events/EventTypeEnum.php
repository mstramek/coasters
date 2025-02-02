<?php

namespace App\Events;

enum EventTypeEnum: string
{
    case COASTER_CREATED = 'coaster.created';
    case COASTER_PATCHED = 'coaster.patched';
    case COASTER_CHECKED = 'coaster.checked';
    case COASTER_STATS_UPDATED = 'coaster.stats.updated';
    case WAGON_CREATED = 'wagon.created';
    case WAGON_DELETED = 'wagon.deleted';

    /**
     * @return EventGroupEnum[]
     */
    public function getGroups(): array
    {
        return match ($this) {
            self::COASTER_CHECKED => [EventGroupEnum::COASTER_CHECKER],
            self::COASTER_STATS_UPDATED => [EventGroupEnum::COASTER_STATS],
            default => [EventGroupEnum::COASTER_DATA],
        };
    }

    public function inGroup(EventGroupEnum $eventGroup): bool
    {
        return in_array($eventGroup, self::getGroups());
    }
}