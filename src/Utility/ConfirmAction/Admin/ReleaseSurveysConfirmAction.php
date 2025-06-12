<?php

namespace App\Utility\ConfirmAction\Admin;

use App\Entity\Enum\Fund;
use App\Entity\FundReturn\FundReturn;
use App\Form\Type\Admin\ConfirmReleaseReturnsType;
use App\Repository\FundReturn\FundReturnRepository;
use App\Utility\FinancialQuarter;
use App\Utility\FundReturnCreator;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\WorkflowInterface;

class ReleaseSurveysConfirmAction extends AbstractConfirmAction
{
    /** @var Fund */
    protected mixed $subject;

    protected FinancialQuarter $financialQuarter;

    public function __construct(
        FormFactoryInterface             $formFactory,
        RequestStack                     $requestStack,
        protected EntityManagerInterface $entityManager,
        protected FundReturnCreator      $fundReturnCreator,
        protected FundReturnRepository   $fundReturnRepository,
        protected Security               $security,
        protected WorkflowInterface      $returnStateStateMachine,
    ) {
        parent::__construct($formFactory, $requestStack);
        $this->financialQuarter = $this->fundReturnCreator->getLatestFinancialQuarterToCreate();
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
    public function getTranslationKeyPrefix(): string
    {
        return 'pages.release_returns';
    }

    #[\Override]
    public function getTranslationParameters(): array
    {
        $year = $this->financialQuarter->initialYear;

        return [
            'fundName' => $this->subject->name,
            'quarter' => $this->financialQuarter->quarter,
            'nextYear' => substr($year, -2),
            'year' => $year,
        ];
    }

    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }



    #[\Override]
    public function doConfirmedAction($formData): void
    {
        $groupedReturns = $this->fundReturnRepository->findFundReturnsForQuarterGroupedByFund($this->financialQuarter);
        $group = $groupedReturns[$this->subject->value] ?? null;

        if ($group === null) {
            throw new \RuntimeException("Cannot release surveys for non-existent fund '{$this->subject->value}'");
        }

        foreach($group['returns'] as $return) {
            if ($return->getState() === FundReturn::STATE_INITIAL) {
                $this->returnStateStateMachine->apply($return, FundReturn::TRANSITION_OPEN_RETURN);
            }
        }
        $this->entityManager->flush();
    }

    public function getFormClass(): string
    {
        return ConfirmReleaseReturnsType::class;
    }
}
