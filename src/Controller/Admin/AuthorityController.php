<?php

namespace App\Controller\Admin;

use App\DataFixtures\FixtureHelper;
use App\DataFixtures\RandomFixtureGenerator;
use App\Entity\Authority;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Form\Type\Admin\AuthorityType;
use App\Form\Type\UserType;
use App\ListPage\AuthorityListPage;
use App\Repository\UserRepository;
use App\Utility\Breadcrumb\Admin\DashboardLinksBuilder;
use App\Utility\SampleReturnGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/authority', name: 'admin_authority')]
class AuthorityController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected SampleReturnGenerator  $sampleReturnGenerator,
    )
    {
    }

    #[Route(path: '', name: '')]
    public function list(
        AuthorityListPage     $listPage,
        Request               $request,
        DashboardLinksBuilder $linksBuilder,
    ): Response
    {
        $linksBuilder->setNavLinks(null);

        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/authority/list.html.twig', [
            'linksBuilder' => $linksBuilder,
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm(),
        ]);
    }

    #[Route(path: '/{id}/view', name: '_view')]
    public function view(
        Authority             $authority,
        UserRepository        $userRepository,
        DashboardLinksBuilder $linksBuilder,
    ): Response
    {
        $linksBuilder->setAtAuthority($authority);

        return $this->render('admin/authority/view.html.twig', [
            'authority' => $authority,
            'linksBuilder' => $linksBuilder,
            'users' => $userRepository->findAllForAuthority($authority),
        ]);
    }

    #[Route(path: '/{id}/edit', name: '_edit')]
    public function edit(
        Authority             $authority,
        DashboardLinksBuilder $linksBuilder,
        Request               $request,
        Session               $session,
        string                $type = 'edit'
    ): Response
    {
        if ($type === 'edit') {
            $linksBuilder->setAtAuthorityEdit($authority);
        } else {
            $linksBuilder->setAtAuthorityAdd();
        }

        /** @var Form $form */
        $form = $this->createForm(AuthorityType::class, $authority, [
            'cancel_url' => $type === 'edit'
                ? $this->generateUrl('admin_authority_view', ['id' => $authority->getId()])
                : $this->generateUrl('admin_authority'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $authority = $form->getData();
            if (!$form->getData()->getId()) {
                $this->entityManager->persist($authority);
                $this->sampleReturnGenerator->createAssetsForNewAuthority($authority);
                $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Authority added', 'The new authority has been added', ['style' => NotificationBanner::STYLE_SUCCESS]));
            } else {
                $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Authority updated', 'The authority has been updated', ['style' => NotificationBanner::STYLE_SUCCESS]));
            }
            if (!$authority?->getAdmin()?->getId()) {
                $this->entityManager->persist($authority->getAdmin());
            }

            $this->entityManager->flush();
            return $this->redirectToRoute('admin_authority_view', ['id' => $authority->getId()]);
        }

        return $this->render('admin/authority/edit.html.twig', [
            'authority' => $form->getData(),
            'linksBuilder' => $linksBuilder,
            'form' => $form,
            'type' => $type,
        ]);
    }

    #[Route(path: '/add', name: '_add')]
    public function add(
        DashboardLinksBuilder $linksBuilder,
        Request               $request,
        Session               $session,
    ): Response
    {
        return $this->edit(new Authority(), $linksBuilder, $request, $session, 'add');
    }

    #[IsGranted(attribute: 'DFT_SUPER_ADMIN')]
    #[Route(path: '/{id}/edit-admin-user', name: '_edit_admin_user')]
    public function editAdmin(
        Authority             $authority,
        DashboardLinksBuilder $linksBuilder,
        Request               $request,
    ): Response
    {
        $linksBuilder->setAtAuthorityEditAdmin($authority);

        $form = $this->createForm(UserType::class, $authority->getAdmin(), [
            'cancel_url' => $this->generateUrl('admin_authority_view', ['id' => $authority->getId()]),
            'authority' => $authority,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            return $this->redirectToRoute('admin_authority_view', ['id' => $authority->getId()]);
        }

        return $this->render('admin/authority/edit_admin_user.html.twig', [
            'authority' => $authority,
            'form' => $form,
            'linksBuilder' => $linksBuilder,
        ]);
    }
}
