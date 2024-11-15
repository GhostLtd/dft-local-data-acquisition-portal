<?php

namespace App\Entity\ProjectFund;

use App\Entity\Enum\Fund;
use Doctrine\ORM\Mapping as ORM;

// N.B. Not currently used - just used to enable testing (e.g. that recipient.getProjectsForFund() works)

#[ORM\Entity]
class BsipProjectFund extends ProjectFund
{
    public function getFund(): Fund
    {
        return Fund::BSIP;
    }

    public function isReturnRequiredFor(int $quarter): bool
    {
        return true;
    }
}
