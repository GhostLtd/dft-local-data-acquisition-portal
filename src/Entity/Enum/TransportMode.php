<?php

namespace App\Entity\Enum;

enum TransportMode: string
{
    // Multi-modal
    case MM_INTERCHANGE_OR_TRAVEL_HUB_WITH_ACTIVE_TRAVEL = "multi-modal.interchange-with-active-travel";
    case MM_INTERCHANGE_OR_TRAVEL_HUB_WITHOUT_ACTIVE_TRAVEL = "multi-modal.interchange-without-active-travel";
    case MM_BUS_AND_ACTIVE_TRAVEL_CORRIDOR = "multi-modal.bus-and-active-travel-corridor";
    case MM_TRAFFIC_REDUCTION_OR_MODAL_SHIFT = "multi-modal.traffic_reduction";
    case MM_OTHER = "multi-modal.other";

    // Active travel
    case AT_IMPROVEMENTS_TO_EXISTING = "active-travel.improvements-to-existing-walking-or-cycle-route";
    case AT_NEW_JUNCTION_TREATMENT = "active-travel.new-junction-treatment";
    case AT_NEW_PERMANENT_FOOTWAY = "active-travel.new-permanent-footway";
    case AT_NEW_ROAD_CROSSINGS  = "active-travel.new-road-crossings";
    case AT_NEW_SEGREGATED_CYCLING_FACILITY = "active-travel.new-segregated-cycling-facility";
    case AT_NEW_SHARED_USE_WALKING_OR_CYCLING = "active-travel.new-shared-use-walking-or-cycling";
    case AT_PROVISION_OF_SECURE_CYCLE_PARKING = "active-travel.provision-of-secure-cycle-parking";
    case AT_RESTRICTION_OR_REDUCTION_OF_CAR_PARKING = "active-travel.restriction-or-reduction-of-car-parking";
    case AT_SCHOOL_STREETS = "active-travel.school-streets";
    case AT_OTHER = "active-travel.other";

    // Bus
    case BUS_PRIORITY_MEASURES = "bus.bus-priority-measures";
    case BUS_OTHER_BUS_AND_COACH_INFRASTRUCTURE = "bus.other-bus-and-coach-infrastructure";
    case BUS_TICKETING_REFORM = "bus.ticketing-reform";
    case BUS_FARES_SUPPORT = "bus.fares-support";
    case BUS_ZERO_EMISSION_BUSES = "bus.zero-emission-buses";
    case BUS_FLEET_UPGRADE = "bus.fleet-upgrade";
    case BUS_OTHER = "bus.other";

    // Rail
    case RAIL_INTERCHANGE_OR_NETWORK_UPGRADE = "rail.interchange-or-network-upgrade";
    case RAIL_OTHER = "rail.other";

    // Tram / metro / light-rail
    case TRAM_INTERCHANGE_OR_NETWORK_UPGRADE = "tram.interchange-or-network-upgrade";
    case TRAM_FLEET_UPGRADE = "tram.fleet-upgrade";
    case TRAM_OTHER = "tram.other";

    // Road
    case ROAD_HIGHWAYS_MAINTENANCE = "road.highways-maintenance";
    case ROAD_EV_CHARGING_INFRASTRUCTURE = "road.ev-charging-infrastructure";
    case ROAD_LOCAL_ROAD_JUNCTION_CONGESTION_OR_SAFETY_IMPROVEMENTS = "road.local-road-junction-congestion-or-safety-improvements";
    case ROAD_OTHER = "road.other";

    // Other
    case OTHER_STAFFING_AND_RESOURCING = "road.other-staffing-and-resourcing";
    case OTHER_OTHER = "other.other";

    /**
     * @return array<TransportMode>
     */
    public static function filterByCategory(string $category): array
    {
        return array_values(
            array_filter(self::cases(), fn(\UnitEnum $e) => str_starts_with($e->value, "{$category}."))
        );
    }

    /**
     * @return array<string>
     */
    public static function getCategories(): array
    {
        return array_values(
            array_unique(
                array_map(fn(\UnitEnum $e) => explode('.', $e->value)[0], self::cases())
            )
        );
    }
}
