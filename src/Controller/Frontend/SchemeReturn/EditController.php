<?php

namespace App\Controller\Frontend\SchemeReturn;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Controller\Frontend\AbstractReturnController;
use App\Entity\Enum\SchemeLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeFund\SchemeFund;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\SchemeReturn\SchemeReturnSectionStatus;
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
    #[Route('/fund-return/{fundReturnId}/scheme/{schemeFundId}/section/{section}', name: 'app_scheme_return_edit')]
    public function schemeReturnEdit(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeFundId)')]
        SchemeFund                 $schemeFund,
        SchemeLevelSection         $section,
        Request                    $request,
    ): Response
    {
        $config = $section->getConfiguration($fundReturn->getFund());

        if (!$config) {
            throw new NotFoundHttpException();
        }

        $schemeReturn = $fundReturn->getSchemeReturnForSchemeFund($schemeFund);
        $breadcrumbBuilder->setAtSchemeFundEdit($fundReturn, $schemeFund, $section);

        $cancelUrl = $this->generateUrl('app_scheme_return', [
            'fundReturnId' => $fundReturn->getId(),
            'schemeFundId' => $schemeFund->getId()
        ])."#{$section->value}";

        $schemeReturnSectionStatus = $this->getSectionStatus($schemeReturn, $section);
        $form = $this->createForm($config->getFormClass(), $schemeReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $schemeReturnSectionStatus,
        ]);

        if ($response = $this->processForm($form, $request, $schemeReturnSectionStatus, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/scheme_return_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'form' => $form,
            'fundReturn' => $fundReturn,
            'schemeReturn' => $schemeReturn,
            'section' => $section,
        ]);
    }

    #[Route('/fund-return/{fundReturnId}/scheme/{schemeFundId}/expense/{divisionKey}', name: 'app_scheme_return_expense_edit')]
    public function schemeReturnExpense(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        string                     $divisionKey,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeFundId)')]
        SchemeFund                 $schemeFund,
        Request                    $request,
        ExpensesTableHelper        $tableHelper,
    ): Response
    {
        $schemeReturn = $fundReturn->getSchemeReturnForSchemeFund($schemeFund);
        $divisionConfiguration = $schemeReturn->findDivisionConfigurationByKey($divisionKey);

        if (!$divisionConfiguration) {
            throw new NotFoundHttpException();
        }

        $breadcrumbBuilder->setAtSchemeExpenseEdit($fundReturn, $schemeFund, $divisionConfiguration);
        $cancelUrl = $this->generateUrl('app_scheme_return', [
            'fundReturnId' => $fundReturn->getId(),
            'schemeFundId' => $schemeFund->getId()
        ])."#expenses-{$divisionKey}";

        $expensesTableHelper = $tableHelper
            ->setDivisionConfiguration($divisionConfiguration)
            ->setRowGroupConfigurations(CrstsHelper::getSchemeExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        $schemeReturnSectionStatus = $this->getSectionStatus($schemeReturn, $divisionConfiguration);
        $form = $this->createForm(ExpensesType::class, $schemeReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $schemeReturnSectionStatus,
            'expenses_table_helper' => $expensesTableHelper,
        ]);

        if ($response = $this->processForm($form, $request, $schemeReturnSectionStatus, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/scheme_return_expenses_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'expensesTable' => $expensesTableHelper->getTable(),
            'form' => $form,
        ]);
    }

    protected function getSectionStatus(SchemeReturn $schemeReturn, DivisionConfiguration|SchemeLevelSection $section): SchemeReturnSectionStatus
    {
        $schemeReturnSectionStatus = $schemeReturn->getOrCreateSchemeReturnSectionStatus($section);
        if (!$this->entityManager->contains($schemeReturnSectionStatus)) {
            $this->entityManager->persist($schemeReturnSectionStatus);
        }
        return $schemeReturnSectionStatus;
    }
}
