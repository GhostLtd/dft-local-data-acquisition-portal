<?php

namespace App\Controller\Frontend\SchemeReturn;

use App\Controller\Frontend\AbstractReturnController;
use App\Entity\Enum\Role;
use App\Entity\Enum\SchemeLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Form\Type\FundReturn\Crsts\ExpensesType;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EditController extends AbstractReturnController
{
    #[Route('/fund-return/{fundReturnId}/scheme/{schemeId}/section/{section}', name: 'app_scheme_return_edit')]

    public function schemeReturnEdit(
        DashboardLinksBuilder $linksBuilder,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn            $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeId)')]
        Scheme                $scheme,
        SchemeLevelSection    $section,
        Request               $request,
    ): Response
    {
        $config = $section->getConfiguration($fundReturn->getFund());

        if (!$config) {
            throw new NotFoundHttpException();
        }

        $schemeReturn = $fundReturn->getSchemeReturnForScheme($scheme);
        $this->denyAccessUnlessGranted(Role::CAN_EDIT, $schemeReturn);
        $linksBuilder->setAtSchemeEdit($fundReturn, $scheme, $section);

        $cancelUrl = $this->generateUrl('app_scheme_return', [
            'fundReturnId' => $fundReturn->getId(),
            'schemeId' => $scheme->getId()
        ])."#{$section->value}";

        $form = $this->createForm($config->getFormClass(), $schemeReturn, [
            'cancel_url' => $cancelUrl,
        ]);

        if ($response = $this->processForm($form, $request, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/scheme_return/edit.html.twig', [
            'linksBuilder' => $linksBuilder,
            'form' => $form,
            'fundReturn' => $fundReturn,
            'schemeReturn' => $schemeReturn,
            'section' => $section,
        ]);
    }

    #[Route('/fund-return/{fundReturnId}/scheme/{schemeId}/expense/{divisionKey}', name: 'app_scheme_return_expense_edit')]
    public function schemeReturnExpense(
        DashboardLinksBuilder $linksBuilder,
        string                $divisionKey,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn            $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeId)')]
        Scheme                $scheme,
        Request               $request,
        ExpensesTableHelper   $tableHelper,
    ): Response
    {
        $schemeReturn = $fundReturn->getSchemeReturnForScheme($scheme);
        $this->denyAccessUnlessGranted(Role::CAN_EDIT_SCHEME_RETURN_EXPENSES, $schemeReturn);
        $divisionConfiguration = $schemeReturn->findDivisionConfigurationByKey($divisionKey);

        if (!$divisionConfiguration) {
            throw new NotFoundHttpException();
        }

        $linksBuilder->setAtSchemeExpenseEdit($fundReturn, $scheme, $divisionConfiguration);
        $cancelUrl = $this->generateUrl('app_scheme_return', [
            'fundReturnId' => $fundReturn->getId(),
            'schemeId' => $scheme->getId()
        ])."#expenses-{$divisionKey}";

        $expensesTableHelper = $tableHelper
            ->setDivisionConfiguration($divisionConfiguration)
            ->setRowGroupConfigurations(CrstsHelper::getSchemeExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        $form = $this->createForm(ExpensesType::class, $schemeReturn, [
            'cancel_url' => $cancelUrl,
            'expenses_table_helper' => $expensesTableHelper,
        ]);

        if ($response = $this->processForm($form, $request, $cancelUrl)) {
            return $response;
        }

        return $this->render('frontend/scheme_return/expenses_edit.html.twig', [
            'linksBuilder' => $linksBuilder,
            'expensesTable' => $expensesTableHelper->getTable(),
            'form' => $form,
        ]);
    }
}
