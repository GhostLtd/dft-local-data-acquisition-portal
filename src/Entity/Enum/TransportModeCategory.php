<?php

namespace App\Entity\Enum;

enum TransportModeCategory: string
{
    case MULTI_MODAL = "multi_modal";
    case ACTIVE_TRAVEL = "active_travel";
    case BUS = "bus";
    case RAIL = "rail";
    case TRAM = "tram";
    case ROAD = "road";
    case OTHER = "other";
}
