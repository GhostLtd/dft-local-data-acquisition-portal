<?php

namespace App\Entity\Enum;

use App\Form\ProjectReturn\Crsts\ProjectDetailsType;

// N.B. Not used in the database, but rather to key slugs + translation keys for project-level returns
enum ProjectLevelSection: string
{
    case PROJECT_DETAILS = 'project_details'; // e.g. name, description, id, retained, cdel/rdel

    public static function getFormClassForFundAndSection(Fund $fund, ProjectLevelSection $section): ?string
    {
        return match($fund) {
            Fund::CRSTS => match($section) {
                self::PROJECT_DETAILS => ProjectDetailsType::class,
            },
            default => null,
        };
    }

    public static function filterForFund(Fund $fund): array
    {
        return match($fund) {
            Fund::CRSTS => [self::PROJECT_DETAILS],
            Fund::BSIP => throw new \RuntimeException('Not yet supported'),
        };
    }
}
