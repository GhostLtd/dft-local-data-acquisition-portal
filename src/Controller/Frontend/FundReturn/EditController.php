<?php

namespace App\Controller\Frontend\FundReturn;

use App\Controller\Frontend\AbstractReturnController;
use App\Entity\Enum\FundLevelSection;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Form\Type\FundReturn\Crsts\ExpensesType;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EditController extends AbstractReturnController
{
    #[Route('/fund-return/{fundReturnId}/section/{section}', name: 'app_fund_return_edit')]
    #[IsGranted(Role::CAN_EDIT, 'fundReturn')]
    public function fundReturnEdit(
        DashboardLinksBuilder $linksBuilder,
        FundLevelSection      $section,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn            $fundReturn,
        Request               $request,
    ): Response
    {
        $formClass = $section::getFormClassForFundAndSection($fundReturn->getFund(), $section);
        if (!$formClass) {
            throw new NotFoundHttpException();
        }

        $linksBuilder->setAtFundReturnSectionEdit($fundReturn, $section);
        $cancelUrl = $this->generateUrl('app_fund_return', ['fundReturnId' => $fundReturn->getId()])."#{$section->value}";

        $form = $this->createForm($formClass, $fundReturn, [
            'cancel_url' => $cancelUrl,
        ]);

        if ($response = $this->processForm($form, $request, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/fund_return_edit.html.twig', [
            'linksBuilder' => $linksBuilder,
            'form' => $form,
            'fundReturn' => $fundReturn,
            'section' => $section,
        ]);
    }

    #[Route('/fund-return/{fundReturnId}/expense/{divisionKey}', name: 'app_fund_return_expense_edit')]
    #[IsGranted(Role::CAN_EDIT, 'fundReturn')]
    public function fundReturnExpense(
        DashboardLinksBuilder $linksBuilder,
        string                $divisionKey,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn            $fundReturn,
        Request               $request,
        ExpensesTableHelper   $tableHelper,
    ): Response
    {
        $divisionConfiguration = $fundReturn->findDivisionConfigurationByKey($divisionKey);

        if (!$divisionConfiguration) {
            throw new NotFoundHttpException();
        }

        $linksBuilder->setAtFundReturnExpenseEdit($fundReturn, $divisionConfiguration);
        $cancelUrl = $this->generateUrl('app_fund_return', [
            'fundReturnId' => $fundReturn->getId()
        ])."#expenses-{$divisionKey}";

        $expensesTableHelper = $tableHelper
            ->setDivisionConfiguration($divisionConfiguration)
            ->setRowGroupConfigurations(CrstsHelper::getFundExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        $form = $this->createForm(ExpensesType::class, $fundReturn, [
            'cancel_url' => $cancelUrl,
            'expenses_table_helper' => $expensesTableHelper,
        ]);

        if ($response = $this->processForm($form, $request, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/fund_return_expenses_edit.html.twig', [
            'linksBuilder' => $linksBuilder,
            'expensesTable' => $expensesTableHelper->getTable(),
            'form' => $form,
        ]);
    }
}
