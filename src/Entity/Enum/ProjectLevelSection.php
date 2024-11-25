<?php

namespace App\Entity\Enum;

use App\Form\ProjectReturn\Crsts\ProjectDetailsType;
use App\Form\ProjectReturn\Crsts\ProjectElementsType;
use App\Form\ProjectReturn\Crsts\ProjectTransportModeType;

// N.B. Not used in the database, but rather to key slugs + translation keys for project-level returns
enum ProjectLevelSection: string
{
    case PROJECT_DETAILS = 'project_details'; // e.g. name, description, id, retained, cdel/rdel
    case TRANSPORT_MODE = 'transport_mode';
    case PROJECT_ELEMENTS = 'project_elements'; // active travel, charging points, clean air elements

    public static function getFormClassForFundAndSection(Fund $fund, ProjectLevelSection $section): ?string
    {
        return match($fund) {
            Fund::CRSTS => match($section) {
                self::PROJECT_DETAILS => ProjectDetailsType::class,
                self::TRANSPORT_MODE => ProjectTransportModeType::class,
                self::PROJECT_ELEMENTS => ProjectElementsType::class,
            },
            default => null,
        };
    }

    public static function filterForFund(Fund $fund): array
    {
        return match($fund) {
            Fund::CRSTS => [self::PROJECT_DETAILS, self::TRANSPORT_MODE, self::PROJECT_ELEMENTS],
            Fund::BSIP => throw new \RuntimeException('Not yet supported'),
        };
    }
}
