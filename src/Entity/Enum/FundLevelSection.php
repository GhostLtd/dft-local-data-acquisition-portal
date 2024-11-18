<?php

namespace App\Entity\Enum;

use App\Form\FundReturn\Crsts\CommentsType;
use App\Form\FundReturn\Crsts\LocalAndRdelType;
use App\Form\FundReturn\Crsts\OverallProgressType;
use App\Form\FundReturn\Crsts\QuarterlyProgressType;

// N.B. Not used in the database, but rather to key slugs + translation keys for fund-level returns
enum FundLevelSection: string
{
    case OVERALL_PROGRESS = 'overall_progress';
    case QUARTERLY_PROGRESS = 'quarterly_progress';
    case LOCAL_AND_RDEL = 'local_and_rdel';
    case COMMENTS = 'comments';


    public static function getFormClassForFundAndSection(Fund $fund, FundLevelSection $section): ?string
    {
        return match($fund) {
            Fund::CRSTS => match($section) {
                self::OVERALL_PROGRESS => OverallProgressType::class,
                self::QUARTERLY_PROGRESS => QuarterlyProgressType::class,
                self::LOCAL_AND_RDEL => LocalAndRdelType::class,
                self::COMMENTS => CommentsType::class,
            },
            default => null,
        };
    }
}
