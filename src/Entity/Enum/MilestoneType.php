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


    // Baseline counterparts of the above
    case BASELINE_START_DEVELOPMENT = "baseline_start_development";
    case BASELINE_END_DEVELOPMENT = "baseline_end_development";
    case BASELINE_START_CONSTRUCTION = "baseline_start_construction";
    case BASELINE_END_CONSTRUCTION = "baseline_end_construction";
    case BASELINE_START_DELIVERY = "baseline_start_delivery";
    case BASELINE_END_DELIVERY = "baseline_end_delivery";
    case BASELINE_FINAL_DELIVERY = "baseline_final_delivery";

    public function isDevelopmentMilestone(): bool
    {
        return match ($this) {
            self::START_DEVELOPMENT,
            self::END_DEVELOPMENT => true,
            default => false,
        };
    }

    public function isBaselineMilestone(): bool
    {
        return str_starts_with($this->name, "BASELINE_");
    }

    public function getBaselineCounterpart(): self
    {
        return self::from('baseline_'.$this->value);
    }

    public function getNonBaselineCounterpart(): self
    {
        return self::from(str_replace('baseline_', '', $this->value));
    }

    public function isCDEL(): bool
    {
        return in_array($this, [
            self::START_DEVELOPMENT,
            self::END_DEVELOPMENT,
            self::START_CONSTRUCTION,
            self::END_CONSTRUCTION,
            self::FINAL_DELIVERY,

            self::BASELINE_START_DEVELOPMENT,
            self::BASELINE_END_DEVELOPMENT,
            self::BASELINE_START_CONSTRUCTION,
            self::BASELINE_END_CONSTRUCTION,
            self::BASELINE_FINAL_DELIVERY,
        ]);
    }

    public function isRDEL(): bool
    {
        return in_array($this, [
            self::START_DEVELOPMENT,
            self::END_DEVELOPMENT,
            self::START_DELIVERY,
            self::END_DELIVERY,

            self::BASELINE_START_DEVELOPMENT,
            self::BASELINE_END_DEVELOPMENT,
            self::BASELINE_START_DELIVERY,
            self::BASELINE_END_DELIVERY,
        ]);
    }

    /** @return array<MilestoneType> */
    public static function getBaselineCases(bool $isCDEL): array
    {
        return array_values(array_filter(
            self::cases(),
            fn(MilestoneType $e) =>
                $e->isBaselineMilestone()
                && ($isCDEL ? $e->isCDEL() : $e->isRDEL())
        ));
    }

    /** @return array<MilestoneType> */
    public static function getNonBaselineCases(bool $isCDEL): array
    {
        return array_values(array_filter(
            self::cases(),
            fn(MilestoneType $e) =>
                !$e->isBaselineMilestone()
                && ($isCDEL ? $e->isCDEL() : $e->isRDEL())
        ));
    }
}
