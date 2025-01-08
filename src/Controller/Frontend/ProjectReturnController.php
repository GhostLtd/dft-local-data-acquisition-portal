<?php

namespace App\Controller\Frontend;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\ProjectLevelSection;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\ProjectReturn\ProjectReturnSectionStatus;
use App\Form\FundReturn\Crsts\ExpensesType;
use App\Utility\Breadcrumb\Frontend\DashboardBreadcrumbBuilder;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProjectReturnController extends AbstractReturnController
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
        $this->denyAccessUnlessGranted(Role::CAN_VIEW, $projectReturn);

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

        $projectReturnSectionStatus = $this->getSectionStatus($projectReturn, $section);
        $form = $this->createForm($config->getFormClass(), $projectReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $projectReturnSectionStatus,
        ]);

        if ($response = $this->processForm($form, $request, $projectReturnSectionStatus, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/project_return_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'form' => $form,
            'fundReturn' => $fundReturn,
            'projectReturn' => $projectReturn,
            'section' => $section,
        ]);
    }

    #[Route('/fund-return/{fundReturnId}/project/{projectFundId}/expense/{divisionKey}', name: 'app_project_return_expense_edit')]
    public function projectReturnExpense(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        string                     $divisionKey,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(projectFundId)')]
        ProjectFund                $projectFund,
        Request                    $request,
        ExpensesTableHelper        $tableHelper,
    ): Response
    {
        $projectReturn = $fundReturn->getProjectReturnForProjectFund($projectFund);
        $divisionConfiguration = $projectReturn->findDivisionConfigurationByKey($divisionKey);

        if (!$divisionConfiguration) {
            throw new NotFoundHttpException();
        }

        $breadcrumbBuilder->setAtProjectExpenseEdit($fundReturn, $projectFund, $divisionConfiguration);
        $cancelUrl = $this->generateUrl('app_project_return', ['fundReturnId' => $fundReturn->getId(), 'projectFundId' => $projectFund->getId()]);

        $expensesTableHelper = $tableHelper
            ->setDivisionConfiguration($divisionConfiguration)
            ->setRowGroupConfigurations(CrstsHelper::getProjectExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        $projectReturnSectionStatus = $this->getSectionStatus($projectReturn, $divisionConfiguration);
        $form = $this->createForm(ExpensesType::class, $projectReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $projectReturnSectionStatus,
            'expenses_table_helper' => $expensesTableHelper,
        ]);

        if ($response = $this->processForm($form, $request, $projectReturnSectionStatus, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/project_return_expenses_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'expensesTable' => $expensesTableHelper->getTableRows(),
            'form' => $form,
        ]);
    }

    protected function getSectionStatus(ProjectReturn $projectReturn, DivisionConfiguration|ProjectLevelSection $section): ProjectReturnSectionStatus
    {
        $projectReturnSectionStatus = $projectReturn->getOrCreateProjectReturnSectionStatus($section);
        if (!$this->entityManager->contains($projectReturnSectionStatus)) {
            $this->entityManager->persist($projectReturnSectionStatus);
        }
        return $projectReturnSectionStatus;
    }
}

