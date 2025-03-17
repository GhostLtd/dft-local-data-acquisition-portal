<?php

namespace App\Security\Voter;

use App\Entity\Enum\Fund;
use App\Entity\Enum\Role;
use App\Entity\Scheme;
use App\Repository\FundReturn\FundReturnRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SchemeCriticalFieldVoter extends Voter
{
    protected array $cachedResults;

    public function __construct(protected FundReturnRepository $fundReturnRepository)
    {
        $this->cachedResults = [];
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            Role::CAN_DELETE_SCHEME,
            Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS,
            Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS,
            Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME,
        ]) && $subject instanceof Scheme;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Scheme) {
            return false;
        }

        $signoffStatesPerFund = $this->areAnyReturnsSignedOffPerFund($subject);

        $fund = match($attribute) {
            Role::CAN_DELETE_SCHEME,
            Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS
                => null,
            Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS,
            Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME
                => Fund::CRSTS1,
            default => throw new \RuntimeException('Unsupported attribute'),
        };

        if ($fund) {
            $relevantSignoffsFound = $signoffStatesPerFund[$fund->value] ?? false;
        } else {
            // Are *any* fund returns involving this scheme signed off?
            $relevantSignoffsFound = array_reduce($signoffStatesPerFund, fn(bool $carry, bool $item) => $carry || $item, false);
        }

        // If there are relevant signed-off returns, we can't edit the critical fields...
        return !$relevantSignoffsFound;
    }

    protected function areAnyReturnsSignedOffPerFund(Scheme $subject): array
    {
        $id = $subject->getId()?->toRfc4122();

        if ($id === null) {
            return [];
        }

        if (!isset($this->cachedResults[$id])) {
            $signedOffByFund = [];

            $fundReturns = $this->fundReturnRepository->findFundReturnsContainingScheme($subject);
            foreach($fundReturns as $fundReturn) {
                $fundValue = $fundReturn->getFund()->value;
                $signedOffByFund[$fundValue] ??= false;

                if ($fundReturn->getSignoffUser() !== null) {
                    $signedOffByFund[$fundValue] = true;
                }
            }

            $this->cachedResults[$id] = $signedOffByFund;
        }

        return $this->cachedResults[$id];
    }
}
