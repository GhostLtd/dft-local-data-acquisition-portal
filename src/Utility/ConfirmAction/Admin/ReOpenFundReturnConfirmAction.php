<?php

namespace App\Utility\ConfirmAction\Admin;

use App\Entity\FundReturn\FundReturn;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\WorkflowInterface;

class ReOpenFundReturnConfirmAction extends AbstractConfirmAction
{
    /** @var FundReturn */
    protected mixed $subject;

    public function __construct(
        FormFactoryInterface             $formFactory,
        RequestStack                     $requestStack,
        protected EntityManagerInterface $entityManager,
        protected Security               $security,
        protected WorkflowInterface      $returnStateStateMachine,
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
            'fund' => $this->subject->getFund()->name,
            'year' => $this->subject->getYear(),
            'nextYear' => $this->subject->getYear() + 1,
            'quarter' => $this->subject->getQuarter(),
        ];
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'admin.pages.fund_return_reopen';
    }

    #[\Override]
    public function doConfirmedAction($formData): void
    {
        $this->returnStateStateMachine->apply($this->subject, FundReturn::TRANSITION_REOPEN_RETURN);
        $this->entityManager->flush();
    }
}
