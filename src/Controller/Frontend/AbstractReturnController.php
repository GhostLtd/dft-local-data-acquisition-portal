<?php

declare(strict_types=1);

namespace App\Controller\Frontend;

use App\Entity\ExpensesContainerInterface;
use App\Entity\SectionStatusInterface;
use App\Form\FundReturn\Crsts\ExpensesType;
use App\Utility\ExpensesTableHelper;
use App\Utility\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Workflow\WorkflowInterface;

abstract class AbstractReturnController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected WorkflowInterface      $completionStateStateMachine,
    ) {}

    protected function handleButton($clickedButton, SectionStatusInterface $sectionStatus): void
    {
        if ($clickedButton === 'save') {
            if ($this->completionStateStateMachine->can($sectionStatus, 'start'))
                $this->completionStateStateMachine->apply($sectionStatus, 'start');
        } else {
            $transitionName = str_replace('transition_', '', $clickedButton);
            $this->completionStateStateMachine->apply($sectionStatus, $transitionName);
        }
    }

    protected function processForm(
        FormInterface $form,
        Request $request,
        SectionStatusInterface $sectionStatus,
        string $cancelUrl,
    ): ?RedirectResponse
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = FormHelper::whichButtonClicked($form);
            if ($clickedButton) {
                $this->handleButton($clickedButton, $sectionStatus);
                $this->entityManager->flush();
                return new RedirectResponse($cancelUrl);
            }
        }
        return null;
    }
}
