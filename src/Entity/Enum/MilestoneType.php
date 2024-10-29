<?php

namespace App\Entity\Enum;

enum MilestoneType: string
{
    case START_DEVELOPMENT = "start_development";
    case END_DEVELOPMENT = "end_development";
    case START_DELIVERY = "start_delivery"; // Text varies - (scheme is RDEL: Start delivery, CDEL: Start construction)
    case END_DELIVERY = "end_delivery"; // Ditto
    case FINAL_DELIVERY = "final_delivery"; // Omitted if scheme is RDEL
}
