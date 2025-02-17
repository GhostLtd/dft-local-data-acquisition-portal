<?php

namespace App\Controller\Frontend\FundReturn;

use App\Entity\Enum\Fund;
use App\Entity\Enum\FundLevelSection;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Form\Type\FundReturn\Crsts\ExpensesTableCalculator;
use App\ListPage\SchemeListPage;
use App\Repository\SchemeFund\SchemeFundRepository;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ViewController extends AbstractController
{
    public function __construct(
        protected DashboardLinksBuilder $linksBuilder,
        protected SchemeFundRepository  $schemeFundRepository,
    ) {}

    #[Route('/fund-return/{fundReturnId}', name: 'app_fund_return')]
    #[IsGranted(Role::CAN_VIEW, 'fundReturn')]
    public function view(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn              $fundReturn,
        ExpensesTableHelper     $expensesTableHelper,
        ExpensesTableCalculator $expensesTableCalculator,
        Request                 $request,
        SchemeListPage          $schemeListPage,
    ): Response
    {
        $this->linksBuilder->setAtFundReturn($fundReturn);
        $fund = $fundReturn->getFund();
        $schemeFunds = $this->getSchemasForFund($fundReturn, $fund);

        $schemeListPage
            ->setFundReturn($fundReturn)
            ->handleRequest($request);

        if ($schemeListPage->isClearClicked()) {
            return new RedirectResponse($schemeListPage->getClearUrl());
        }

        $expensesTableHelper
            ->setRowGroupConfigurations(CrstsHelper::getFundExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        return $this->render('frontend/fund_return/view.html.twig', [
            'linksBuilder' => $this->linksBuilder,
            'expenseDivisions' => $fundReturn->getDivisionConfigurations(),
            'fundLevelSections' => FundLevelSection::filterForFund($fund),
            'fundReturn' => $fundReturn,
            'expensesTableHelper' => $expensesTableHelper,
            'expensesTableCalculator' => $expensesTableCalculator,
            'schemeFunds' => $schemeFunds,
            'schemeListPage' => $schemeListPage,
        ]);
    }

    protected function getSchemasForFund(FundReturn $fundReturn, Fund $fund): array
    {
        // We get the schemeFunds from this direction, so that we can list all of them and explicitly any that
        // do not requiring a return, if that is the case (e.g. CRSTS - if not retained and not quarter 1)

        // (Fetching via fundReturn->getSchemeReturns() direction would only fetch those schemes that
        //  do have returns, resulting in an incomplete list)
        return $this->schemeFundRepository->getSchemeFundsForAuthority(
            $fundReturn->getFundAward()->getAuthority(),
            $fund
        );
    }
}
