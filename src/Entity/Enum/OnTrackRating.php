<?php

namespace App\Entity\Enum;

enum OnTrackRating: string
{
    case GREEN = "green";
    case GREEN_AMBER = "green_amber";
    case AMBER = "amber";
    case AMBER_RED = "amber_red";
    case RED = "red";
    case SCHEME_COMPLETED = "scheme_completed";
    case SCHEME_ON_HOLD = "scheme_on_hold";
    case SCHEME_COMPLETED_LATE = "scheme_completed_late";
    case SCHEME_CANCELLED = "scheme_cancelled";
    case SCHEME_NA = "not_applicable";

    public function getTagClass(): string
    {
        return 'govuk-tag--' . match ($this) {
                self::RED => 'red',
                self::AMBER_RED => 'pink',
                self::AMBER => 'orange',
                self::GREEN_AMBER => 'yellow',
                self::GREEN => 'green',

                self::SCHEME_COMPLETED_LATE, self::SCHEME_COMPLETED => 'blue',

                default => 'grey',
            };
    }
}
