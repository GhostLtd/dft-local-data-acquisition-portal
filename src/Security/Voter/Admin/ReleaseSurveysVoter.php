<?php

namespace App\Security\Voter\Admin;

use App\Entity\Enum\Fund;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\UserTypeRoles;
use App\Repository\FundReturn\FundReturnRepository;
use App\Utility\FundReturnCreator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ReleaseSurveysVoter extends Voter
{
    public function __construct(
        protected AccessDecisionManagerInterface $accessDecisionManager,
        protected FundReturnCreator              $fundReturnCreator,
        protected FundReturnRepository           $fundReturnRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_RELEASE_RETURNS &&
            $subject instanceof Fund;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$this->accessDecisionManager->decide($token, [UserTypeRoles::ROLE_IAP_ADMIN])) {
            return false;
        }

        $financialQuarter = $this->fundReturnCreator->getLatestFinancialQuarterToCreate();
        $groupedReturns = $this->fundReturnRepository->findFundReturnsForQuarterGroupedByFund($financialQuarter);

        $groupedReturns = $groupedReturns[$subject->value] ?? null;

        if (!$groupedReturns) {
            return false;
        }

        foreach($groupedReturns['returns'] as $return) {
            if ($return->getState() === FundReturn::STATE_INITIAL) {
                // There are surveys that can be transitioned from initial to open
                return true;
            }
        }

        return false;
    }
}
