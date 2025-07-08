<?php

namespace App\Controller\Frontend\FundReturn;

use App\Entity\Enum\Fund;
use App\Entity\Enum\FundLevelSection;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Form\Type\FundReturn\Crsts\ExpensesTableCalculator;
use App\Form\Type\FundReturn\Crsts\MilestoneBaselineTableHelper;
use App\ListPage\SchemeListPage;
use App\Repository\SchemeRepository;
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
        protected SchemeRepository $schemeRepository,
    )
    {
    }

    #[Route('/fund-return/{fundReturnId}', name: 'app_fund_return')]
    #[IsGranted(Role::CAN_VIEW, 'fundReturn')]
    public function view(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                   $fundReturn,
        ExpensesTableHelper          $expensesTableHelper,
        ExpensesTableCalculator      $expensesTableCalculator,
        DashboardLinksBuilder        $linksBuilder,
        MilestoneBaselineTableHelper $milestoneBaselineTableHelper,
        Request                      $request,
        SchemeListPage               $schemeListPage,
    ): Response
    {
        $linksBuilder->setAtFundReturn($fundReturn);
        $fund = $fundReturn->getFund();
        $schemeFunds = $this->getSchemesForFund($fundReturn, $fund);

        $schemeListPage
            ->setFundReturn($fundReturn)
            ->handleRequest($request);

        if ($schemeListPage->isClearClicked()) {
            return new RedirectResponse($schemeListPage->getClearUrl());
        }

        $isInitialState = $fundReturn->getState() === FundReturn::STATE_INITIAL;

        if ($isInitialState) {
            $expensesTableHelper->setConfiguration(CrstsHelper::getFundBaselinesTable($fundReturn->getYear(), $fundReturn->getQuarter()));
            $milestonesTable = $milestoneBaselineTableHelper->setFundReturn($fundReturn)->getTable();
        } else {
            $expensesTableHelper->setConfiguration(CrstsHelper::getFundExpensesTable($fundReturn->getYear(), $fundReturn->getQuarter()));
            $milestonesTable = null;
        }

        return $this->render($request->attributes->get('template', 'frontend/fund_return/view.html.twig'), [
            'linksBuilder' => $linksBuilder,
            'expensesTableCalculator' => $expensesTableCalculator,
            'expensesTableHelper' => $expensesTableHelper,
            'fundLevelSections' => FundLevelSection::filterForFund($fund),
            'fundReturn' => $fundReturn,
            'milestonesTable' => $milestonesTable,
            'returnYearDivisionKey' => CrstsHelper::getDivisionConfigurationKey($fundReturn->getYear()),
            'schemeFunds' => $schemeFunds,
            'schemeListPage' => $schemeListPage,
        ]);
    }

    protected function getSchemesForFund(FundReturn $fundReturn, Fund $fund): array
    {
        // We get the schemeFunds from this direction, so that we can list all of them and explicitly any that
        // do not requiring a return, if that is the case (e.g. CRSTS - if not retained and not quarter 1)

        // (Fetching via fundReturn->getSchemeReturns() direction would only fetch those schemes that
        //  do have returns, resulting in an incomplete list)
        return $this->schemeRepository->getSchemesForAuthority($fundReturn->getFundAward()->getAuthority());
    }
}
