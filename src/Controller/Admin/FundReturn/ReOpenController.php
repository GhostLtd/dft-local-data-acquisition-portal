<?php

namespace App\Controller\Admin\FundReturn;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use App\Utility\ConfirmAction\Admin\ReOpenFundReturnConfirmAction;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReOpenController extends AbstractController
{
    #[IsGranted(Role::CAN_REOPEN_RETURN, 'fundReturn')]
    #[Route('/fund-return/{fundReturnId}/reopen', name: 'app_fund_return_reopen')]
    #[Template('frontend/fund_return/reopen.html.twig')]
    public function reOpen(
        Request                       $request,
        DashboardLinksBuilder         $linksBuilder,
        ReOpenFundReturnConfirmAction $reOpenFundReturnConfirmAction,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                    $fundReturn,
    ): RedirectResponse|array {
        $linksBuilder->setAtFundReturnReOpen($fundReturn);

        return $reOpenFundReturnConfirmAction
            ->setSubject($fundReturn)
            ->setExtraViewData([
                'linksBuilder' => $linksBuilder,
                'fundReturn' => $fundReturn,
            ])
            ->controller(
                $request,
                $this->generateUrl('admin_fund_return', ['fundReturnId' => $fundReturn->getId()])
            );
    }
}
