<?php

namespace App\Entity\SchemeFund;

use App\Entity\Enum\Fund;
use App\Entity\SchemeFund\SchemeFund;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// N.B. Not currently used - just used to enable testing (e.g. that authority.getSchemesForFund() works)

#[ORM\Entity]
class BsipSchemeFund extends SchemeFund
{
    public function getFund(): Fund
    {
        return Fund::BSIP;
    }

    public function isReturnRequiredFor(int $quarter): bool
    {
        return true;
    }

    public function getReturns(): Collection
    {
        return new ArrayCollection();
    }
}
