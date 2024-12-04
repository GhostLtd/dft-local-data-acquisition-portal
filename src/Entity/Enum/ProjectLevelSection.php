<?php

namespace App\Entity\Enum;

use App\Entity\Config\ProjectLevelSectionConfiguration;
use App\Form\ProjectReturn\Crsts\OverallFundingType;
use App\Form\ProjectReturn\Crsts\ProjectDetailsType;
use App\Form\ProjectReturn\Crsts\ProjectElementsType;
use App\Form\ProjectReturn\Crsts\ProjectTransportModeType;

// N.B. Not used in the database, but rather to key slugs + translation keys for project-level returns
enum ProjectLevelSection: string
{
    case PROJECT_DETAILS = 'project_details'; // e.g. name, description, id, retained, cdel/rdel
    case TRANSPORT_MODE = 'transport_mode';
    case PROJECT_ELEMENTS = 'project_elements'; // active travel, charging points, clean air elements

    case OVERALL_FUNDING = 'overall_funding'; // total scheme cost, agreed crsts funding

    /**
     * @return array<ProjectLevelSectionConfiguration>
     */
    public static function getConfigurationForFund(Fund $fund): array
    {
        return match($fund) {
            Fund::CRSTS1 => [
                new ProjectLevelSectionConfiguration(self::PROJECT_DETAILS, ProjectDetailsType::class),
                new ProjectLevelSectionConfiguration(self::TRANSPORT_MODE, ProjectTransportModeType::class),
                new ProjectLevelSectionConfiguration(self::PROJECT_ELEMENTS, ProjectElementsType::class),
                new ProjectLevelSectionConfiguration(self::OVERALL_FUNDING, OverallFundingType::class, isDisplayedInExpensesList: true),
            ],
            default => [],
        };
    }

    public function getConfiguration(Fund $fund): ?ProjectLevelSectionConfiguration
    {
        foreach(self::getConfigurationForFund($fund) as $configuration) {
            if ($configuration->getSection() === $this) {
                return $configuration;
            }
        }

        return null;
    }
}
