<?php

namespace App\Entity\Enum;

use Symfony\Component\Translation\TranslatableMessage;

enum ActiveTravelElement: string
{
    case NO_ACTIVE_TRAVEL_ELEMENTS = "no_active_travel_elements";
    case AREA_WIDE_TRAFFIC_MANAGEMENT = "area_wide_traffic_management";
    case BUS_PRIORITY_MEASURES = "bus_priority_measures";
    case ROUTE_IMPROVEMENTS = "route_improvements";
    case NEW_JUNCTION_TREATMENT = "new_junction_treatment";
    case NEW_PERMANENT_FOOTWAY = "new_permanent_footway";
    case NEW_ROAD_CROSSINGS = "new_road_crossings";
    case NEW_SEGREGATED_CYCLING = "new_segregated_cycling";
    case NEW_SHARED_USE = "new_shared_use";
    case PROVISION_OF_SECURE_CYCLE_PARKING = "provision_of_secure_cycle_parking";
    case RESTRICTION_OR_REDUCTION_OF_PARKING = "restriction_or_reduction_of_parking";
    case SCHOOL_STREETS = "school_streets";
    case OTHER = "other";

    /**
     * @return array<static>
     */
    public static function casesExcludingNoElements(): array
    {
        return array_filter(self::cases(), fn(ActiveTravelElement $e) => $e !== ActiveTravelElement::NO_ACTIVE_TRAVEL_ELEMENTS);
    }

    public function isNoActiveElement(): bool
    {
        return $this->value !== ActiveTravelElement::NO_ACTIVE_TRAVEL_ELEMENTS->value;
    }

    public function getForDisplay(): ?TranslatableMessage
    {
        if (!$this->value) {
            return null;
        }
        return new TranslatableMessage(
            "enum.active_travel_element.{$this->value}"
        );
    }
}
