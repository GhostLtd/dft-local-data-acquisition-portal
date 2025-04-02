<?php

namespace App\Controller\Frontend;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\User;
use App\Repository\MaintenanceWarningRepository;
use App\Repository\AuthorityRepository;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use App\Utility\UserReachableEntityResolver;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        DashboardLinksBuilder        $linksBuilder,
        MaintenanceWarningRepository $maintenanceWarningRepository,
        AuthorityRepository          $authorityRepository,
        ?UserInterface               $user,
        UserReachableEntityResolver  $userReachableEntityResolver,
    ): Response
    {
        if (!$user instanceof User) {
            // Not-logged-in homepage...
            return $this->render('frontend/home.html.twig', [
                'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
            ]);
        }

        $authorityIds = $userReachableEntityResolver->getAuthorityIdsViewableBy($user);

        if (count($authorityIds) === 1) {
            return new RedirectResponse($this->generateUrl('app_dashboard_authority', ['authorityId' => $authorityIds[0]]));
        }

        return $this->render('frontend/dashboard.html.twig', [
            'linksBuilder' => $linksBuilder,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
            'authorities' => $authorityRepository->getAuthoritiesFundAwardsAndReturns($authorityIds),
        ]);
    }

    #[IsGranted(Role::CAN_VIEW, 'authority')]
    #[Route('/authority/{authorityId}', name: 'app_dashboard_authority')]
    public function authority(
        #[MapEntity(expr: 'repository.find(authorityId)')]
        Authority                    $authority,
        DashboardLinksBuilder        $linksBuilder,
        MaintenanceWarningRepository $maintenanceWarningRepository,
    ): Response
    {
        $linksBuilder->setAtAuthority($authority);

        return $this->render('frontend/authority.html.twig', [
            'authority' => $authority,
            'linksBuilder' => $linksBuilder,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
        ]);
    }
}
