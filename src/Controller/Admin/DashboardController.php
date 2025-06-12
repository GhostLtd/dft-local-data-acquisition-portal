<?php

namespace App\Controller\Admin;

use App\Repository\FundReturn\FundReturnRepository;
use App\Repository\MaintenanceWarningRepository;
use App\Utility\Breadcrumb\Admin\LatestReturnsLinksBuilder;
use App\Utility\FundReturnCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        FundReturnCreator            $fundReturnCreator,
        FundReturnRepository         $fundReturnRepository,
        LatestReturnsLinksBuilder    $linksBuilder,
        MaintenanceWarningRepository $maintenanceWarningRepository,
    ): Response
    {
        $financialQuarter = $fundReturnCreator->getLatestFinancialQuarterToCreate();
        $groupedReturns = $fundReturnRepository->findFundReturnsForQuarterGroupedByFund($financialQuarter);

        $linksBuilder->setAtDashboard();

        return $this->render('admin/dashboard.html.twig', [
            'linksBuilder' => $linksBuilder,
            'groupedReturns' => $groupedReturns,
            'latestQuarter' => $financialQuarter,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }
}
