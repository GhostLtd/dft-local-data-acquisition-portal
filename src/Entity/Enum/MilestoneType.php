<?php

namespace App\Entity\Enum;

enum MilestoneType: string
{
    case START_DEVELOPMENT = "start_development";
    case END_DEVELOPMENT = "end_development";
    case START_CONSTRUCTION = "start_construction"; // CRSTS: CDEL-only
    case END_CONSTRUCTION = "end_construction"; // CRSTS: CDEL-only
    case START_DELIVERY = "start_delivery"; // CRSTS: RDEL-only
    case END_DELIVERY = "end_delivery"; // CRSTS: RDEL-only
    case FINAL_DELIVERY = "final_delivery"; // CRSTS: CDEL-only

    public function isDevelopmentMilestone(): bool
    {
        return match ($this) {
            self::START_DEVELOPMENT, self::END_DEVELOPMENT => true,
            default => false,
        };
    }
}
