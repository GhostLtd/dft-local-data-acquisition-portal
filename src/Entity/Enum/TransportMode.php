<?php

namespace App\Entity\Enum;

use Symfony\Component\Translation\TranslatableMessage;

enum TransportMode: string
{
    // Multi-modal
    case MM_INTERCHANGE_OR_TRAVEL_HUB = "multi_modal.interchange";
    case MM_BUS_AND_ACTIVE_TRAVEL_CORRIDOR = "multi_modal.bus_and_active_travel_corridor";
    case MM_TRAFFIC_REDUCTION_OR_MODAL_SHIFT = "multi_modal.traffic_reduction";
    case MM_OTHER = "multi_modal.other";

    // Active travel
    case AT_IMPROVEMENTS_TO_EXISTING = "active_travel.improvements_to_existing_walking_or_cycle_route";
    case AT_NEW_JUNCTION_TREATMENT = "active_travel.new_junction_treatment";
    case AT_NEW_PERMANENT_FOOTWAY = "active_travel.new_permanent_footway";
    case AT_NEW_ROAD_CROSSINGS  = "active_travel.new_road_crossings";
    case AT_NEW_SEGREGATED_CYCLING_FACILITY = "active_travel.new_segregated_cycling_facility";
    case AT_NEW_SHARED_USE_WALKING_OR_CYCLING = "active_travel.new_shared_use_walking_or_cycling";
    case AT_PROVISION_OF_SECURE_CYCLE_PARKING = "active_travel.provision_of_secure_cycle_parking";
    case AT_RESTRICTION_OR_REDUCTION_OF_CAR_PARKING = "active_travel.restriction_or_reduction_of_car_parking";
    case AT_SCHOOL_STREETS = "active_travel.school_streets";
    case AT_OTHER = "active_travel.other";

    // Bus
    case BUS_PRIORITY_MEASURES = "bus.bus_priority_measures";
    case BUS_OTHER_BUS_AND_COACH_INFRASTRUCTURE = "bus.other_bus_and_coach_infrastructure";
    case BUS_TICKETING_REFORM = "bus.ticketing_reform";
    case BUS_FARES_SUPPORT = "bus.fares_support";
    case BUS_ZERO_EMISSION_BUSES = "bus.zero_emission_buses";
    case BUS_FLEET_UPGRADE = "bus.fleet_upgrade";
    case BUS_OTHER = "bus.other";

    // Rail
    case RAIL_INTERCHANGE_OR_NETWORK_UPGRADE = "rail.interchange_or_network_upgrade";
    case RAIL_OTHER = "rail.other";

    // Tram / metro / light-rail
    case TRAM_INTERCHANGE_OR_NETWORK_UPGRADE = "tram.interchange_or_network_upgrade";
    case TRAM_FLEET_UPGRADE = "tram.fleet_upgrade";
    case TRAM_OTHER = "tram.other";

    // Road
    case ROAD_HIGHWAYS_MAINTENANCE = "road.highways_maintenance";
    case ROAD_EV_CHARGING_INFRASTRUCTURE = "road.ev_charging_infrastructure";
    case ROAD_LOCAL_ROAD_JUNCTION_CONGESTION_OR_SAFETY_IMPROVEMENTS = "road.local_road_junction_congestion_or_safety_improvements";
    case ROAD_OTHER = "road.other";

    // Other
    case OTHER_STAFFING_AND_RESOURCING = "other.staffing_and_resourcing";
    case OTHER_OTHER = "other.other";

    /**
     * @return array<TransportMode>
     */
    public static function filterByCategory(TransportModeCategory $category): array
    {
        return array_values(
            array_filter(self::cases(), fn(TransportMode $e) => str_starts_with($e->value, "{$category->value}."))
        );
    }

    public function category(): TransportModeCategory
    {
        $categoryValue = substr($this->value, 0, strpos($this->value, "."));
        return TransportModeCategory::from($categoryValue);
    }

    public function isActiveTravel(): bool
    {
        return $this->category() === TransportModeCategory::ACTIVE_TRAVEL;
    }

    public function getForDisplay(): ?TranslatableMessage
    {
        if (!$this->value) {
            return null;
        }
        return new TranslatableMessage(
            "enum.transport_mode.full_display",
            [
                "{category}" => new TranslatableMessage("enum.transport_mode.categories.{$this->category()->value}"),
                "{mode}" => new TranslatableMessage("enum.transport_mode.{$this->value}"),
            ]
        );
    }
}
