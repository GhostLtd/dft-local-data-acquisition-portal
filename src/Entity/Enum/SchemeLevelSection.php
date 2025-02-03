<?php

namespace App\Entity\Enum;

use App\Config\SchemeLevelSectionConfiguration;
use App\Form\Type\SchemeReturn\Crsts\MilestoneBusinessCaseType;
use App\Form\Type\SchemeReturn\Crsts\MilestoneDatesType;
use App\Form\Type\SchemeReturn\Crsts\MilestoneRatingType;
use App\Form\Type\SchemeReturn\Crsts\OverallFundingType;
use App\Form\Type\SchemeReturn\Crsts\SchemeDetailsType;
use App\Form\Type\SchemeReturn\Crsts\SchemeElementsType;
use App\Form\Type\SchemeReturn\Crsts\SchemeTransportModeType;

// N.B. Not used in the database, but rather as keys + translation keys for scheme-level returns
enum SchemeLevelSection: string
{
    case SCHEME_DETAILS = 'scheme_details'; // e.g. name, description, id, retained, cdel/rdel
    case TRANSPORT_MODE = 'transport_mode';
    case SCHEME_ELEMENTS = 'scheme_elements'; // active travel, charging points, clean air elements

    case OVERALL_FUNDING = 'overall_funding'; // total scheme cost, agreed crsts funding
    case MILESTONE_DATES = 'milestone_dates'; // start/end dev/construction, final delivery
    case MILESTONE_BUSINESS = 'milestone_business_case'; // business case state + dates
    case MILESTONE_PROGRESS = 'milestone_progress'; // rating and progress update

    /**
     * @return array<SchemeLevelSectionConfiguration>
     */
    public static function getConfigurationForFund(Fund $fund): array
    {
        return match($fund) {
            Fund::CRSTS1 => [
                new SchemeLevelSectionConfiguration(self::SCHEME_DETAILS, SchemeDetailsType::class),
                new SchemeLevelSectionConfiguration(self::TRANSPORT_MODE, SchemeTransportModeType::class),
                new SchemeLevelSectionConfiguration(self::SCHEME_ELEMENTS, SchemeElementsType::class),
                new SchemeLevelSectionConfiguration(self::OVERALL_FUNDING, OverallFundingType::class, displayGroup: SectionDisplayGroup::EXPENSES),
                new SchemeLevelSectionConfiguration(self::MILESTONE_DATES, MilestoneDatesType::class, displayGroup: SectionDisplayGroup::MILESTONES),
                new SchemeLevelSectionConfiguration(self::MILESTONE_BUSINESS, MilestoneBusinessCaseType::class, displayGroup: SectionDisplayGroup::MILESTONES),
                new SchemeLevelSectionConfiguration(self::MILESTONE_PROGRESS, MilestoneRatingType::class, displayGroup: SectionDisplayGroup::MILESTONES),
            ],
            default => [],
        };
    }

    public function getConfiguration(Fund $fund): ?SchemeLevelSectionConfiguration
    {
        foreach(self::getConfigurationForFund($fund) as $configuration) {
            if ($configuration->getSection() === $this) {
                return $configuration;
            }
        }

        return null;
    }
}
