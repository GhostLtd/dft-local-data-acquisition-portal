<?php

namespace App\Entity\Enum;

enum MilestoneType: string
{
    case START_DEVELOPMENT = "start_development";
    case END_DEVELOPMENT = "end_development";
    case START_DELIVERY = "start_delivery"; // Depends upon whether RDEL or not
    case END_DELIVERY = "end_delivery"; // Depends upon whether RDEL or not
    case FINAL_DELIVERY = "final_delivery";
}
