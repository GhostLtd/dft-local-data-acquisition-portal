<?php

namespace App\Controller\Frontend;

use App\Entity\Recipient;
use App\Repository\MaintenanceWarningRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        MaintenanceWarningRepository $maintenanceWarningRepository,
    ): Response
    {
        return $this->render('frontend/dashboard.html.twig', [
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }

    #[Route('/recipients/{id}', name: 'app_recipient')]
    public function recipient(Recipient $recipient): Response
    {
        return $this->render('frontend/recipient.html.twig', [
            'recipient' => $recipient,
        ]);
    }
}
