<?php

namespace App\Controller\Admin;

use App\Repository\MaintenanceWarningRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(MaintenanceWarningRepository $maintenanceWarningRepository): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }
}
