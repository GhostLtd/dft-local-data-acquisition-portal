<?php

namespace App\Entity\Enum;

enum OnTrackRating: string
{
    case GREEN = "green";
    case GREEN_AMBER = "green_amber";
    case AMBER = "amber";
    case AMBER_RED = "amber_red";
    case RED = "red";
    case PROJECT_COMPLETED = "project_completed";
    case PROJECT_ON_HOLD = "project_on_hold";
    case PROJECT_COMPLETED_LATE = "project_completed_late";
    case PROJECT_CANCELLED = "project_cancelled";
    case PROJECT_NA = "not_applicable";
}
