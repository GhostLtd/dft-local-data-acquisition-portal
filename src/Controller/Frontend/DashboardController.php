<?php

namespace App\Controller\Frontend;

use App\Repository\MaintenanceWarningRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(MaintenanceWarningRepository $maintenanceWarningRepository): Response
    {
        return $this->render('frontend/dashboard.html.twig', [
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }
}
