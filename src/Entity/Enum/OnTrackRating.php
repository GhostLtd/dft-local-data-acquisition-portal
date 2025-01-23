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
}
