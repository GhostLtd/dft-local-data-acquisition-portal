<?php

namespace App\Controller\Frontend;

use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\FundLevelSection;
use App\Entity\Enum\ProjectLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Event\FundReturnSectionUpdateEvent;
use App\Event\ProjectReturnSectionUpdateEvent;
use App\Form\FundReturn\Crsts\ExpensesType;
use App\Utility\Breadcrumb\Frontend\DashboardBreadcrumbBuilder;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use App\Utility\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ProjectReturnController extends AbstractController
{
    #[Route('/fund-return/{fundReturnId}/project/{projectFundId}', name: 'app_project_return')]
    public function projectReturn(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(projectFundId)')]
        ProjectFund                $projectFund,
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
    ): Response
    {
        $projectReturn = $fundReturn->getProjectReturnForProjectFund($projectFund);
        $breadcrumbBuilder->setAtProjectFund($fundReturn, $projectFund);

        // TODO: Check projectFund belongs to fundReturn

        $fund = $fundReturn->getFund();

        return $this->render('frontend/project_return.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'fundReturn' => $fundReturn,
            'projectReturn' => $projectReturn,
            'projectLevelSectionsConfiguration' => ProjectLevelSection::getConfigurationForFund($fund),
            'expenseDivisions' => $fundReturn->getDivisionConfigurations(),
        ]);
    }


    #[Route('/fund-return/{fundReturnId}/project/{projectFundId}/section/{section}', name: 'app_project_return_edit')]
    public function projectReturnEdit(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        EntityManagerInterface     $entityManager,
        EventDispatcherInterface   $eventDispatcher,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(projectFundId)')]
        ProjectFund                $projectFund,
        ProjectLevelSection        $section,
        Request                    $request,
    ): Response
    {
        $config = $section->getConfiguration($fundReturn->getFund());

        if (!$config) {
            throw new NotFoundHttpException();
        }

        $projectReturn = $fundReturn->getProjectReturnForProjectFund($projectFund);
        $breadcrumbBuilder->setAtProjectFundEdit($fundReturn, $projectFund, $section);

        $cancelUrl = $this->generateUrl('app_project_return', [
            'fundReturnId' => $fundReturn->getId(),
            'projectFundId' => $projectFund->getId()
        ]);

        $form = $this->createForm($config->getFormClass(), $projectReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $projectReturn->getStatusForSection($section),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = FormHelper::whichButtonClicked($form);
            if ($clickedButton) {
                $eventDispatcher->dispatch(new ProjectReturnSectionUpdateEvent($projectReturn, $section, ['mode' => $clickedButton]));
                $entityManager->flush();
                return new RedirectResponse($cancelUrl);
            }
        }

        return $this->render('frontend/project_return_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'form' => $form,
            'fundReturn' => $fundReturn,
            'projectReturn' => $projectReturn,
            'section' => $section,
        ]);
    }

    #[Route('/fund-return/{fundReturnId}/project/{projectFundId}/expense/{divisionSlug}', name: 'app_project_return_expense_edit')]
    public function projectReturnExpense(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        EntityManagerInterface     $entityManager,
        EventDispatcherInterface   $eventDispatcher,
        string                     $divisionSlug,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(projectFundId)')]
        ProjectFund                $projectFund,
        Request                    $request,
        ExpensesTableHelper        $tableHelper,
    ): Response
    {
        $projectReturn = $fundReturn->getProjectReturnForProjectFund($projectFund);
        $divisionConfiguration = $projectReturn->findDivisionConfigurationBySlug($divisionSlug);

        if (!$divisionConfiguration) {
            throw new NotFoundHttpException();
        }

        $breadcrumbBuilder->setAtProjectExpenseEdit($fundReturn, $projectFund, $divisionConfiguration);
        $cancelUrl = $this->generateUrl('app_project_return', ['fundReturnId' => $fundReturn->getId(), 'projectFundId' => $projectFund->getId()]);

        $expensesTableHelper = $tableHelper
            ->setDivisionConfiguration($divisionConfiguration)
            ->setRowGroupConfigurations(CrstsHelper::getProjectExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        $form = $this->createForm(ExpensesType::class, $projectReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $fundReturn->getStatusForSection($divisionConfiguration),
            'expenses_table_helper' => $expensesTableHelper,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = FormHelper::whichButtonClicked($form);
            if ($clickedButton) {
                $eventDispatcher->dispatch(new ProjectReturnSectionUpdateEvent($projectReturn, $divisionConfiguration, ['mode' => $clickedButton]));
                $entityManager->flush();
                return new RedirectResponse($cancelUrl);
            }
        }

        return $this->render('frontend/project_return_expenses_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'expensesTable' => $expensesTableHelper->getTableRows(),
            'form' => $form,
        ]);
    }
}
