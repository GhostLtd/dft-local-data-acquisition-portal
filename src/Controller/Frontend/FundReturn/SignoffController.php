<?php

namespace App\Controller\Frontend\FundReturn;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use App\Utility\ConfirmAction\Frontend\SignoffFundReturnConfirmAction;
use App\Utility\SignoffHelper\SignoffHelperFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SignoffController extends AbstractController
{
    #[IsGranted(Role::CAN_SIGN_OFF_RETURN, 'fundReturn')]
    #[IsGranted(Role::CAN_RETURN_BE_SIGNED_OFF, 'fundReturn')]
    #[Route('/fund-return/{fundReturnId}/signoff', name: 'app_fund_return_signoff')]
    #[Template('frontend/fund_return/signoff.html.twig')]
    public function signoff(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                     $fundReturn,
        DashboardLinksBuilder          $linksBuilder,
        Request                        $request,
        SignoffFundReturnConfirmAction $signoffFundReturnConfirmAction,
    ): RedirectResponse|array {
        $linksBuilder->setAtFundReturnSignoff($fundReturn);

        return $signoffFundReturnConfirmAction
            ->setSubject($fundReturn)
            ->setExtraViewData([
                'fundReturn' => $fundReturn,
                'linksBuilder' => $linksBuilder,
            ])
            ->controller(
                $request,
                $this->generateUrl('app_fund_return', ['fundReturnId' => $fundReturn->getId()])
            );
    }

    #[IsGranted(Role::CAN_VIEW, 'fundReturn')]
    #[Route('/fund-return/{fundReturnId}/signoff-issues', name: 'app_fund_return_signoff_issues')]
    public function viewSignoffIssues(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                     $fundReturn,
        DashboardLinksBuilder          $linksBuilder,
        Request                        $request,
        SignoffHelperFactory           $signoffHelperFactory,
    ): Response {
        $signoffHelper = $signoffHelperFactory
            ->getHelperFor($fundReturn)
            ->setUseAdminLinks($request->attributes->get('useAdminLinks', false));

        $signoffEligibilityStatus = $signoffHelper->getSignoffEligibilityStatus($fundReturn);

        $linksBuilder->setAtViewIssuesPreventingSignoff($fundReturn);

        return $this->render($request->attributes->get('template', 'frontend/fund_return/signoff_issues.html.twig'), [
            'fundReturn' => $fundReturn,
            'linksBuilder' => $linksBuilder,
            'signoffEligibilityStatus' => $signoffEligibilityStatus,
        ]);
    }
}
