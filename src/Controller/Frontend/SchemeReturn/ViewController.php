<?php

namespace App\Controller\Frontend\SchemeReturn;

use App\Entity\Enum\Role;
use App\Entity\Enum\SchemeLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Form\Type\FundReturn\Crsts\ExpensesTableCalculator;
use App\Repository\SchemeRepository;
use App\Repository\SchemeReturn\SchemeReturnRepository;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewController extends AbstractController
{
    #[Route('/fund-return/{fundReturnId}/scheme/{schemeId}', name: 'app_scheme_return')]
    public function view(
        Request $request,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn              $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeId)')]
        Scheme                  $scheme,
        DashboardLinksBuilder   $linksBuilder,
        ExpensesTableHelper     $expensesTableHelper,
        ExpensesTableCalculator $expensesTableCalculator,
        SchemeReturnRepository  $schemeReturnRepository,
        SchemeRepository        $schemeRepository,
    ): Response
    {
        $schemeReturn = $fundReturn->getSchemeReturnForScheme($scheme);
        $this->denyAccessUnlessGranted(Role::CAN_VIEW, $schemeReturn);

        $linksBuilder->setAtScheme($fundReturn, $scheme);

        $fund = $fundReturn->getFund();

        $expensesTableHelper = $expensesTableHelper
            ->setConfiguration(CrstsHelper::getSchemeExpensesTable($fundReturn->getYear(), $fundReturn->getQuarter()));

        return $this->render($request->attributes->get('template', 'frontend/scheme_return/view.html.twig'), [
            'expensesTableHelper' => $expensesTableHelper,
            'expensesTableCalculator' => $expensesTableCalculator,
            'fundReturn' => $fundReturn,
            'linksBuilder' => $linksBuilder,
            'nonEditablePoint' => $schemeReturnRepository->cachedFindPointWhereReturnBecameNonEditable($schemeReturn),
            'returnYearDivisionKey' => CrstsHelper::getDivisionConfigurationKey($fundReturn->getYear()),
            'previousAndNextSchemes' => $schemeRepository->getPreviousAndNextSchemes($fundReturn, $scheme),
            'schemeReturn' => $schemeReturn,
            'schemeLevelSectionsConfiguration' => SchemeLevelSection::getConfigurationForFund($fund),
        ]);
    }
}
