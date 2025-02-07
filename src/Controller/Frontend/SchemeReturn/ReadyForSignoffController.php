<?php

namespace App\Controller\Frontend\SchemeReturn;

use App\Controller\Frontend\AbstractReturnController;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeFund\SchemeFund;
use App\Utility\Breadcrumb\Frontend\DashboardBreadcrumbBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Form\ConfirmActionType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class ReadyForSignoffController extends AbstractReturnController
{
    protected const string MARK_AS_READY = 'mark_as_ready';
    protected const string MARK_AS_NOT_READY = 'mark_as_not_ready';

    public function __construct(
        EntityManagerInterface               $entityManager,
        protected DashboardBreadcrumbBuilder $breadcrumbBuilder,
    )
    {
        parent::__construct($entityManager);
    }

    #[Route('/fund-return/{fundReturnId}/scheme/{schemeFundId}/mark-as-ready-for-signoff', name: 'app_scheme_return_mark_as_ready_for_signoff')]
    public function schemeReadyForSignoff(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeFundId)')]
        SchemeFund $schemeFund,
        Request    $request,
    ): Response
    {
        return $this->generateResponse($fundReturn, $schemeFund, $request, self::MARK_AS_READY);
    }

    #[Route('/fund-return/{fundReturnId}/scheme/{schemeFundId}/mark-as-not-ready-for-signoff', name: 'app_scheme_return_mark_as_not_ready_for_signoff')]
    public function schemeNotReadyForSignoff(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeFundId)')]
        SchemeFund $schemeFund,
        Request    $request,
    ): Response
    {
        return $this->generateResponse($fundReturn, $schemeFund, $request, self::MARK_AS_NOT_READY);
    }

    protected function generateResponse(
        FundReturn $fundReturn,
        SchemeFund $schemeFund,
        Request    $request,
        string     $type,
    ): Response
    {
        $schemeReturn = $fundReturn->getSchemeReturnForSchemeFund($schemeFund);

        if ($type === self::MARK_AS_READY) {
            $this->denyAccessUnlessGranted(Role::CAN_MARK_AS_READY, $schemeReturn);
            $this->breadcrumbBuilder->setAtSchemeReadyForSignoff($fundReturn, $schemeFund);
            $label = "forms.scheme.mark_as_ready_for_signoff.confirm";
            $template = "frontend/scheme_return/ready_for_signoff.html.twig";
        } else if ($type === self::MARK_AS_NOT_READY) {
            $this->denyAccessUnlessGranted(Role::CAN_MARK_AS_NOT_READY, $schemeReturn);
            $this->breadcrumbBuilder->setAtSchemeNotReadyForSignoff($fundReturn, $schemeFund);
            $label = "forms.scheme.mark_as_not_ready_for_signoff.confirm";
            $template = "frontend/scheme_return/not_ready_for_signoff.html.twig";
        } else {
            throw new \InvalidArgumentException('Unexpected value for $type');
        }

        $cancelUrl = $this->generateUrl('app_scheme_return', [
            'fundReturnId' => $fundReturn->getId(),
            'schemeFundId' => $schemeFund->getId()
        ]);

        $form = $this->createForm(ConfirmActionType::class, null, [
            'cancel_link_options' => [
                'href' => $cancelUrl,
            ],
            'confirm_button_options' => [
                'label' => $label,
            ],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $button = $form->get('button_group')->get('confirm');
            if ($button instanceof SubmitButton && $button->isClicked()) {
                $value = match($type) {
                    self::MARK_AS_READY => true,
                    self::MARK_AS_NOT_READY => false,
                };

                $schemeReturn->setReadyForSignoff($value);
                $this->entityManager->flush();

                return new RedirectResponse($cancelUrl);
            }
        }

        return $this->render($template, [
            'breadcrumbBuilder' => $this->breadcrumbBuilder,
            'form' => $form,
            'fundReturn' => $fundReturn,
            'schemeReturn' => $schemeReturn,
        ]);
    }
}
