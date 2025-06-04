<?php

namespace App\Controller\Admin;

use App\Repository\FundReturn\FundReturnRepository;
use App\Repository\MaintenanceWarningRepository;
use App\Utility\Breadcrumb\Admin\DashboardLinksBuilder;
use App\Utility\FinancialQuarter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        DashboardLinksBuilder        $linksBuilder,
        MaintenanceWarningRepository $maintenanceWarningRepository,
        FundReturnRepository         $fundReturnRepository,
    ): Response
    {
        // N.B. We might be in say 2025 Q1, but we want to show returns for the latest
        //      completed quarter, which would be 2024 Q4, as that'll be the latest return
        //      (since figures are available because that quarter is over)
        $financialQuarter = FinancialQuarter::createFromDate(new \DateTime())
            ->getPreviousQuarter();

        $groupedReturns = $fundReturnRepository->findFundReturnsForQuarterGroupedByFund($financialQuarter);

        return $this->render('admin/dashboard.html.twig', [
            'groupedReturns' => $groupedReturns,
            'latestQuarter' => $financialQuarter,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }
}
