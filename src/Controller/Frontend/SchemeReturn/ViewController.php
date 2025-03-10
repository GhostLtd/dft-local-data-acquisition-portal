<?php

namespace App\Controller\Frontend\SchemeReturn;

use App\Entity\Enum\Role;
use App\Entity\Enum\SchemeLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
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
    #[Route('/fund-return/{fundReturnId}/scheme/{schemeId}', name: 'app_scheme_return')]
    public function view(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn              $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeId)')]
        Scheme                  $scheme,
        DashboardLinksBuilder   $linksBuilder,
        ExpensesTableHelper     $expensesTableHelper,
        ExpensesTableCalculator $expensesTableCalculator,
    ): Response
    {
        $schemeReturn = $fundReturn->getSchemeReturnForScheme($scheme);
        $this->denyAccessUnlessGranted(Role::CAN_VIEW, $schemeReturn);

        $linksBuilder->setAtScheme($fundReturn, $scheme);

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
