<?php

namespace App\Controller\Frontend\FundReturn;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Utility\Breadcrumb\Frontend\DashboardBreadcrumbBuilder;
use App\Utility\ConfirmAction\Frontend\SignoffFundReturnConfirmAction;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SignoffController extends AbstractController
{
    #[IsGranted(Role::CAN_SIGN_OFF_RETURN, 'fundReturn')]
    #[Route('/fund-return/{fundReturnId}/signoff', name: 'app_fund_return_signoff')]
    #[Template('frontend/fund_return/signoff.html.twig')]
    public function delete(
        Request $request,
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        SignoffFundReturnConfirmAction $signoffFundReturnConfirmAction,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn $fundReturn,
    ): RedirectResponse|array {
        $breadcrumbBuilder->setAtFundReturnSignoff($fundReturn);

        return $signoffFundReturnConfirmAction
            ->setSubject($fundReturn)
            ->setExtraViewData([
                'breadcrumbBuilder' => $breadcrumbBuilder,
                'fundReturn' => $fundReturn,
            ])
            ->controller(
                $request,
                $this->generateUrl('app_fund_return', ['fundReturnId' => $fundReturn->getId()])
            );
    }

}