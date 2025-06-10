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

    public function isCDEL(): bool
    {
        return in_array($this, [
            self::START_DEVELOPMENT,
            self::END_DEVELOPMENT,
            self::START_CONSTRUCTION,
            self::END_CONSTRUCTION,
            self::FINAL_DELIVERY,
        ]);
    }

    public function isRDEL(): bool
    {
        return in_array($this, [
            self::START_DEVELOPMENT,
            self::END_DEVELOPMENT,
            self::START_DELIVERY,
            self::END_DELIVERY,
        ]);
    }

    /** @return array<MilestoneType> */
    public static function getNonBaselineCases(?bool $isCDEL=null): array
    {
        return array_values(array_filter(
            self::cases(),
            fn(MilestoneType $e) =>
                (
                    $isCDEL === null
                    || ($isCDEL ? $e->isCDEL() : $e->isRDEL())
                )
        ));
    }
}
