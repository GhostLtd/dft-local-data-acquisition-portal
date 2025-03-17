<?php

namespace App\Utility\ConfirmAction\Frontend;

use App\Entity\Scheme;
use App\Utility\SchemeReturnHelper\SchemeReturnHelper;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;


class SchemeDeleteConfirmAction extends AbstractConfirmAction
{
    /** @var Scheme */
    protected mixed $subject;

    public function __construct(
        FormFactoryInterface             $formFactory,
        RequestStack                     $requestStack,
        protected EntityManagerInterface $entityManager,
        protected SchemeReturnHelper     $schemeReturnHelper,
    ) {
        parent::__construct($formFactory, $requestStack);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
            ],
        ]);
    }

    #[\Override]
    public function getTranslationParameters(): array
    {
        return [
            'schemeName' => $this->subject->getName(),
            'schemeIdentifer' => $this->subject->getSchemeIdentifier(),
        ];
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'frontend.pages.scheme_delete';
    }

    #[\Override]
    public function doConfirmedAction($formData): void
    {
        // If we ever want to remove schemes that *do* have submitted returns,
        // then this code will return the relevant schemeReturns, allowing the
        // scheme to be deleted too.

//        $schemeFunds = $this->subject->getFunds();
//        $fundAwards = $this->subject->getAuthority()->getFundAwards();
//
//        foreach($fundAwards as $fundAward) {
//            if (!in_array($fundAward->getType(), $schemeFunds)) {
//                continue;
//            }
//
//            foreach($fundAward->getReturns() as $return) {
//                foreach($return->getSchemeReturns() as $schemeReturn) {
//                    if ($schemeReturn->getScheme() === $this->subject) {
//                        $return->removeSchemeReturn($schemeReturn);
//                        $this->entityManager->remove($schemeReturn);
//                    }
//                }
//            }
//        }

        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}
