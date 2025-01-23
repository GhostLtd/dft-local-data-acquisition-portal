<?php

namespace App\Entity\Enum;

enum MilestoneType: string
{
    case START_DEVELOPMENT = "start_development";
    case END_DEVELOPMENT = "end_development";
    case START_CONSTRUCTION = "start_construction"; // Text varies - (scheme is CDEL: Start construction, RDEL: Start delivery)
    case END_CONSTRUCTION = "end_construction"; // Ditto
    case FINAL_DELIVERY = "final_delivery"; // Omitted if scheme is RDEL
}
