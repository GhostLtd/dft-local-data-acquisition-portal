<?php

namespace App\Form\Type\Admin;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\Enum\Role;
use App\Entity\FundAward;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FundAwardDataMapper implements DataMapperInterface
{
    public function __construct(protected AuthorizationCheckerInterface $authorizationChecker)
    {}

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof Authority) {
            throw new UnexpectedTypeException($viewData, Authority::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $fundTypes = array_map(fn(FundAward $f) => $f->getType()->value, $viewData->getFundAwards()->toArray());
        $forms['funds']->setData($fundTypes);
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof Authority) {
            throw new UnexpectedTypeException($viewData, Authority::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $formFunds = $forms['funds']->getData();

        foreach($viewData->getFundAwards() as $fundAward) {
            $type = $fundAward->getType()->value;
            if (!isset($formFunds[$type])) {
                if ($this->authorizationChecker->isGranted(Role::CAN_REMOVE_FUND_AWARD, $fundAward)) {
                    $viewData->removeFundAward($fundAward);
                }
            }
        }

        foreach($formFunds as $fund) {
            foreach($viewData->getFundAwards() as $fundAward) {
                if ($fundAward->getType()->value === $fund) {
                    continue 2;
                }
            }

            $fundAward = (new FundAward())->setType(Fund::from($fund));
            $viewData->addFundAward($fundAward);
        }
    }
}