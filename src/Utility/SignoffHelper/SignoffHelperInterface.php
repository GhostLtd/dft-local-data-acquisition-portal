<?php

namespace App\Utility\SignoffHelper;

use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(SignoffHelperInterface::class)]
interface SignoffHelperInterface
{
    public function supports(FundReturn|SchemeReturn $return): bool;
    public function setUseAdminLinks(bool $useAdminLinks): static;
    public function getSignoffEligibilityStatus(FundReturn|SchemeReturn $return): SignoffEligibilityStatus;
    public function hasSignoffEligibilityProblems(FundReturn|SchemeReturn $return): bool;
}
