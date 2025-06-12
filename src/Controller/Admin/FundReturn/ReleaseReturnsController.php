<?php

namespace App\Controller\Admin\FundReturn;

use App\Entity\Enum\Fund;
use App\Entity\Enum\Role;
use App\Utility\Breadcrumb\Admin\LatestReturnsLinksBuilder;
use App\Utility\ConfirmAction\Admin\ReleaseSurveysConfirmAction;
use App\Utility\FundReturnCreator;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReleaseReturnsController extends AbstractController
{
    #[IsGranted(Role::CAN_RELEASE_RETURNS, 'fund')]
    #[Route('/fund/{fund}/release-returns', name: 'admin_fund_release_returns')]
    #[Template('admin/fund/release_returns.html.twig')]
    public function releaseReturns(
        Request                       $request,
        LatestReturnsLinksBuilder     $linksBuilder,
        ReleaseSurveysConfirmAction   $releaseSurveysConfirmAction,
        Fund                          $fund,
        FundReturnCreator             $fundReturnCreator,
    ): RedirectResponse|array {

        $financialQuarter = $fundReturnCreator->getLatestFinancialQuarterToCreate();
        $linksBuilder->setAtReleaseSurveys($fund, $financialQuarter->quarter, $financialQuarter->initialYear);

        return $releaseSurveysConfirmAction
            ->setSubject($fund)
            ->setExtraViewData([
                'linksBuilder' => $linksBuilder,
                'fund' => $fund,
            ])
            ->controller(
                $request,
                $this->generateUrl('admin_dashboard').'#latest-'.$fund->value,
            );
    }
}
