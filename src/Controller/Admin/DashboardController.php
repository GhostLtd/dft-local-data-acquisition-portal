<?php

namespace App\Controller\Admin;

use App\Repository\FundReturn\FundReturnRepository;
use App\Repository\MaintenanceWarningRepository;
use App\Utility\FundReturnCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        MaintenanceWarningRepository $maintenanceWarningRepository,
        FundReturnCreator            $fundReturnCreator,
        FundReturnRepository         $fundReturnRepository,
    ): Response
    {
        $financialQuarter = $fundReturnCreator->getLatestFinancialQuarterToCreate();
        $groupedReturns = $fundReturnRepository->findFundReturnsForQuarterGroupedByFund($financialQuarter);

        return $this->render('admin/dashboard.html.twig', [
            'groupedReturns' => $groupedReturns,
            'latestQuarter' => $financialQuarter,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }
}
