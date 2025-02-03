<?php

namespace App\Controller\Frontend\FundReturn;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Controller\Frontend\AbstractReturnController;
use App\Entity\Enum\FundLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\FundReturn\FundReturnSectionStatus;
use App\Form\Type\FundReturn\Crsts\ExpensesType;
use App\Utility\Breadcrumb\Frontend\DashboardBreadcrumbBuilder;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EditController extends AbstractReturnController
{


    #[Route('/fund-return/{fundReturnId}/section/{section}', name: 'app_fund_return_edit')]
    public function fundReturnEdit(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        FundLevelSection           $section,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        Request                    $request,
    ): Response
    {
        $formClass = $section::getFormClassForFundAndSection($fundReturn->getFund(), $section);
        if (!$formClass) {
            throw new NotFoundHttpException();
        }

        $breadcrumbBuilder->setAtFundReturnSectionEdit($fundReturn, $section);
        $cancelUrl = $this->generateUrl('app_fund_return', ['fundReturnId' => $fundReturn->getId()])."#{$section->value}";

        $fundReturnSectionStatus = $this->getSectionStatus($fundReturn, $section);
        $form = $this->createForm($formClass, $fundReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $fundReturnSectionStatus,
        ]);

        if ($response = $this->processForm($form, $request, $fundReturnSectionStatus, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/fund_return_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'form' => $form,
            'fundReturn' => $fundReturn,
            'section' => $section,
        ]);
    }

    #[Route('/fund-return/{fundReturnId}/expense/{divisionKey}', name: 'app_fund_return_expense_edit')]
    public function fundReturnExpense(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        string                     $divisionKey,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        Request                    $request,
        ExpensesTableHelper        $tableHelper,
    ): Response
    {
        $divisionConfiguration = $fundReturn->findDivisionConfigurationByKey($divisionKey);

        if (!$divisionConfiguration) {
            throw new NotFoundHttpException();
        }

        $breadcrumbBuilder->setAtFundReturnExpenseEdit($fundReturn, $divisionConfiguration);
        $cancelUrl = $this->generateUrl('app_fund_return', [
            'fundReturnId' => $fundReturn->getId()
        ])."#expenses-{$divisionKey}";

        $expensesTableHelper = $tableHelper
            ->setDivisionConfiguration($divisionConfiguration)
            ->setRowGroupConfigurations(CrstsHelper::getFundExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        $fundReturnSectionStatus = $this->getSectionStatus($fundReturn, $divisionConfiguration);
        $form = $this->createForm(ExpensesType::class, $fundReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $fundReturnSectionStatus,
            'expenses_table_helper' => $expensesTableHelper,
        ]);

        if ($response = $this->processForm($form, $request, $fundReturnSectionStatus, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/fund_return_expenses_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'expensesTable' => $expensesTableHelper->getTable(),
            'form' => $form,
        ]);
    }

    protected function getSectionStatus(FundReturn $fundReturn, DivisionConfiguration|FundLevelSection $section): FundReturnSectionStatus
    {
        $fundReturnSectionStatus = $fundReturn->getOrCreateFundReturnSectionStatus($section);
        if (!$this->entityManager->contains($fundReturnSectionStatus)) {
            $this->entityManager->persist($fundReturnSectionStatus);
        }
        return $fundReturnSectionStatus;
    }
}
