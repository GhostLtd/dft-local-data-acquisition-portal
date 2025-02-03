<?php

namespace App\Entity\Enum;

use App\Form\Type\FundReturn\Crsts\CommentsType;
use App\Form\Type\FundReturn\Crsts\LocalAndRdelType;
use App\Form\Type\FundReturn\Crsts\OverallProgressType;
use App\Form\Type\FundReturn\Crsts\QuarterlyProgressType;

// N.B. Not used in the database, but rather as keys + translation keys for fund-level returns
enum FundLevelSection: string
{
    case OVERALL_PROGRESS = 'overall_progress';
    case QUARTERLY_PROGRESS = 'quarterly_progress';
    case LOCAL_AND_RDEL = 'local_and_rdel';
    case COMMENTS = 'comments';


    public static function getFormClassForFundAndSection(Fund $fund, FundLevelSection $section): ?string
    {
        return match($fund) {
            Fund::CRSTS1 => match($section) {
                self::OVERALL_PROGRESS => OverallProgressType::class,
                self::QUARTERLY_PROGRESS => QuarterlyProgressType::class,
                self::LOCAL_AND_RDEL => LocalAndRdelType::class,
                self::COMMENTS => CommentsType::class,
            },
            default => null,
        };
    }

    public static function filterForFund(Fund $fund): array
    {
        return match($fund) {
            Fund::CRSTS1 => [self::OVERALL_PROGRESS, self::QUARTERLY_PROGRESS, self::LOCAL_AND_RDEL, self::COMMENTS],
            Fund::CRSTS2, Fund::BSIP => throw new \RuntimeException('Not yet supported'),
        };
    }
}
