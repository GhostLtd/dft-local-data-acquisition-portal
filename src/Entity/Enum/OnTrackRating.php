<?php

namespace App\Entity\Enum;

enum OnTrackRating: string
{
    case GREEN = "green";
    case AMBER = "amber";
    case RED = "red";
    case SCHEME_COMPLETED = "scheme_completed";
    case SCHEME_CANCELLED = "scheme_cancelled";
    case SCHEME_SPLIT = "scheme_split";
    case SCHEME_MERGED = "scheme_merged";

    public function getTagClass(): string
    {
        return 'govuk-tag--' . match ($this) {
            self::RED => 'red',
            self::AMBER => 'orange',
            self::GREEN => 'green',

            self::SCHEME_COMPLETED,
            self::SCHEME_CANCELLED,
            self::SCHEME_MERGED,
            self::SCHEME_SPLIT => 'blue',
        };
    }
}
