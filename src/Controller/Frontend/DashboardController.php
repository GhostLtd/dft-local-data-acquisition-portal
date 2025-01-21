<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Repository\MaintenanceWarningRepository;
use App\Repository\AuthorityRepository;
use App\Utility\Breadcrumb\Frontend\DashboardBreadcrumbBuilder;
use App\Utility\UserReachableEntityResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        DashboardBreadcrumbBuilder   $breadcrumbBuilder,
        MaintenanceWarningRepository $maintenanceWarningRepository,
        AuthorityRepository          $authorityRepository,
        UserInterface                $user,
        UserReachableEntityResolver  $userReachableEntityResolver,
    ): Response
    {
        if (!$user instanceof User) {
            throw new NotFoundHttpException();
        }

        $authorityIds = $userReachableEntityResolver->getAuthorityIdsViewableBy($user);

        return $this->render('frontend/dashboard.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
            'authorities' => $authorityRepository->getAuthoritiesFundAwardsAndReturns($authorityIds),
        ]);
    }
}
