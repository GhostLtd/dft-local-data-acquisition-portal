<?php

namespace App\Entity\Enum;

use App\Config\SchemeLevelSectionConfiguration;
use App\Form\Type\SchemeReturn\Crsts\MilestoneBusinessCaseType;
use App\Form\Type\SchemeReturn\Crsts\MilestoneDatesType;
use App\Form\Type\SchemeReturn\Crsts\MilestoneRatingType;
use App\Form\Type\SchemeReturn\Crsts\OverallFundingType;

// N.B. Not used in the database, but rather as keys + translation keys for scheme-level returns
enum SchemeLevelSection: string
{
    case OVERALL_FUNDING = 'overall_funding'; // total scheme cost, agreed crsts funding, bcr
    case MILESTONE_DATES = 'milestone_dates'; // start/end dev/construction, final delivery
    case MILESTONE_BUSINESS = 'milestone_business_case'; // business case state + dates
    case MILESTONE_PROGRESS = 'milestone_progress'; // rating, progress update, and scheme risks

    /**
     * @return array<SchemeLevelSectionConfiguration>
     */
    public static function getConfigurationForFund(Fund $fund): array
    {
        return match($fund) {
            Fund::CRSTS1 => [
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
