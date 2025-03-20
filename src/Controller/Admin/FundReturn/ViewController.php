<?php

namespace App\Controller\Admin\FundReturn;

use App\Controller\Admin\ForwardRouteTrait;
use App\ListPage\SchemeListPage;
use App\Utility\Breadcrumb\Admin\DashboardLinksBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;

class ViewController extends AbstractController
{
    use ForwardRouteTrait;

    #[Route('/fund-return/{fundReturnId}', name: 'admin_fund_return')]
    public function view(
        Request $request,
        SchemeListPage $schemeListPage,
        DashboardLinksBuilder $linksBuilder,
    ): Response
    {
        $schemeListPage->setRouteName('admin_fund_return');
        $request->attributes->set('template', 'admin/fund_return/view.html.twig');
        $request->attributes->set('linksBuilder', $linksBuilder);

        return $this->forward("App\Controller\Frontend\FundReturn\ViewController::view", $request->attributes->all(), $request->query->all());
    }
}
