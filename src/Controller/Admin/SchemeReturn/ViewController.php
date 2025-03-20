<?php

namespace App\Controller\Admin\SchemeReturn;

use App\Controller\Admin\ForwardRouteTrait;
use App\Utility\Breadcrumb\Admin\DashboardLinksBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ViewController extends AbstractController
{
    use ForwardRouteTrait;

    #[Route('/fund-return/{fundReturnId}/scheme/{schemeId}', name: 'admin_scheme_return')]
    public function view(
        Request $request,
        DashboardLinksBuilder $linksBuilder,
    ): Response
    {
        $request->attributes->set('template', 'admin/scheme_return/view.html.twig');
        $request->attributes->set('linksBuilder', $linksBuilder);
        return $this->forward("App\Controller\Frontend\SchemeReturn\ViewController::view", $request->attributes->all());
    }
}
