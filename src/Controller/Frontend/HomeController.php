<?php

namespace App\Controller\Frontend;

use App\Repository\MaintenanceWarningRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_test')]
    public function index(
        MaintenanceWarningRepository $maintenanceWarningRepository,
        ?UserInterface               $user,
    ): Response
    {
        if ($user) {
            return new RedirectResponse($this->generateUrl('app_dashboard'));
        }

        return $this->render('frontend/home.html.twig', [
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }
}
