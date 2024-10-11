<?php

namespace App\Entity\Enum;

enum BusinessCase: string
{
    case WORKING_TOWARDS_SOBC = "working_towards_sobc";
    case WORKING_TOWARDS_OBC = "working_towards_obc";
    case WORKING_TOWARDS_FBC = "working_towards_fbc";
    case POST_FBC = "post_fbc";
    case NOT_APPLICABLE = "not_applicable";
}