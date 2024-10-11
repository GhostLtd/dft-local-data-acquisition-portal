<?php

namespace App\Entity\Enum;

enum Rating: string
{
    case GREEN = "green";
    case GREEN_AMBER = "green_amber";
    case AMBER = "amber";
    case AMBER_RED = "amber_red";
    case RED = "red";
}
