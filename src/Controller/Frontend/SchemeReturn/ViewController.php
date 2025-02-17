<?php

namespace App\Controller\Frontend\SchemeReturn;

use App\Entity\Enum\Role;
use App\Entity\Enum\SchemeLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeFund\SchemeFund;
use App\Form\Type\FundReturn\Crsts\ExpensesTableCalculator;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewController extends AbstractController
{
    #[Route('/fund-return/{fundReturnId}/scheme/{schemeFundId}', name: 'app_scheme_return')]
    public function view(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn              $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeFundId)')]
        SchemeFund              $schemeFund,
        DashboardLinksBuilder   $linksBuilder,
        ExpensesTableHelper     $expensesTableHelper,
        ExpensesTableCalculator $expensesTableCalculator,
    ): Response
    {
        $schemeReturn = $fundReturn->getSchemeReturnForSchemeFund($schemeFund);
        $this->denyAccessUnlessGranted(Role::CAN_VIEW, $schemeReturn);

        $linksBuilder->setAtSchemeFund($fundReturn, $schemeFund);

        $fund = $fundReturn->getFund();

        $expensesTableHelper = $expensesTableHelper
            ->setRowGroupConfigurations(CrstsHelper::getSchemeExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        return $this->render('frontend/scheme_return/view.html.twig', [
            'linksBuilder' => $linksBuilder,
            'expenseDivisions' => $fundReturn->getDivisionConfigurations(),
            'expensesTableHelper' => $expensesTableHelper,
            'expensesTableCalculator' => $expensesTableCalculator,
            'fundReturn' => $fundReturn,
            'schemeReturn' => $schemeReturn,
            'schemeLevelSectionsConfiguration' => SchemeLevelSection::getConfigurationForFund($fund),
        ]);
    }
}
