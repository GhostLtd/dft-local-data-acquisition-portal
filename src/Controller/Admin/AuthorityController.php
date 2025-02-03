<?php

namespace App\Controller\Admin;

use App\Entity\Authority;
use App\ListPage\AuthorityListPage;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/authority', name: 'admin_authority')]
class AuthorityController extends AbstractController
{
    #[Route(path: '', name: '')]
    public function list(AuthorityListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/authority/list.html.twig', [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: '_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, Session $session, Authority $authority, string $type='edit'): Response
    {
        /** @var Form $form */
        $form = $this->createForm(AuthorityType::class, $authority);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->getClickedButton()->getName() === 'cancel') {
                return $this->redirectToRoute('admin_authority');
            }

            if ($form->isValid()) {
                if (!$form->getData()->getId()) {
                    $entityManager->persist($form->getData());
                    $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Authority added', 'The new authority has been added', ['style' => NotificationBanner::STYLE_SUCCESS]));
                } else {
                    $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Authority updated', 'The authority has been updated', ['style' => NotificationBanner::STYLE_SUCCESS]));
                }
                $entityManager->flush();
                return $this->redirectToRoute('admin_authority');
            }
        }

        return $this->render('admin/authority/edit.html.twi', [
            'form' => $form,
            'authority' => $form->getData(),
            'type' => $type,
        ]);
    }

    #[Route(path: '/add', name: '_add')]
    public function add(Request $request, EntityManagerInterface $entityManager, Session $session): Response
    {
        return $this->edit($request, $entityManager, $session, new Authority(), 'add');
    }
}
