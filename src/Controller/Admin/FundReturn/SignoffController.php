<?php

namespace App\Controller\Admin\FundReturn;

use App\Controller\Admin\ForwardRouteTrait;
use App\Utility\Breadcrumb\Admin\DashboardLinksBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SignoffController extends AbstractController
{
    use ForwardRouteTrait;

    #[Route('/fund-return/{fundReturnId}/signoff-issues', name: 'admin_fund_return_signoff_issues')]
    public function viewSignoffIssues(
        Request $request,
        DashboardLinksBuilder $linksBuilder,
    ): Response
    {
        $request->attributes->set('linksBuilder', $linksBuilder);
        $request->attributes->set('template', 'admin/fund_return/signoff_issues.html.twig');
        $request->attributes->set('useAdminLinks', true);

        return $this->forward("App\Controller\Frontend\FundReturn\SignoffController::viewSignoffIssues", $request->attributes->all(), $request->query->all());
    }
}
