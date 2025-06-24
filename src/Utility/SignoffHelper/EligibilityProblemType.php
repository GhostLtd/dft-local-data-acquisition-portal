<?php

namespace App\Utility\SignoffHelper;

enum EligibilityProblemType: string
{
    case MISSING_FORECAST = 'missing_forecast';
    case ON_TRACK_RATING_EMPTY = 'on_track_rating_empty';
}
