<?php

namespace App\Controller\Admin;

use App\Entity\MaintenanceWarning;
use App\Form\Type\Admin\MaintenanceWarningType;
use App\ListPage\MaintenanceWarningListPage;
use App\Utility\ConfirmAction\Admin\DeleteMaintenanceWarningConfirmAction;
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

#[Route(path: '/maintenance-warning', name: 'admin_maintenance')]
class MaintenanceWarningController extends AbstractController
{
    #[Route(path: '', name: '')]
    public function list(MaintenanceWarningListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/maintenance_warning/list.html.twig', [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: '_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, Session $session, MaintenanceWarning $maintenanceWarning, string $type='edit'): Response
    {
        /** @var Form $form */
        $form = $this->createForm(MaintenanceWarningType::class, $maintenanceWarning, [
            'cancel_url' => $this->generateUrl('admin_maintenance'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->getClickedButton()->getName() === 'cancel') {
                return $this->redirectToRoute('admin_maintenance');
            }

            if ($form->isValid()) {
                if (!$form->getData()->getId()) {
                    $entityManager->persist($form->getData());
                    $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Maintenance warning added', 'The new maintenance warning has been added', ['style' => NotificationBanner::STYLE_SUCCESS]));
                } else {
                    $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Maintenance warning updated', 'The maintenance warning has been updated', ['style' => NotificationBanner::STYLE_SUCCESS]));
                }
                $entityManager->flush();
                return $this->redirectToRoute('admin_maintenance');
            }
        }

        return $this->render('admin/maintenance_warning/edit.html.twig', [
            'form' => $form,
            'maintenanceWarning' => $form->getData(),
            'type' => $type,
        ]);
    }

    #[Route(path: '/add', name: '_add')]
    public function add(Request $request, EntityManagerInterface $entityManager, Session $session): Response
    {
        return $this->edit($request, $entityManager, $session, new MaintenanceWarning(), 'add');
    }

    #[Route(path: '{id}/delete', name: '_delete')]
    #[Template('admin/maintenance_warning/delete.html.twig')]
    public function delete(Request $request, DeleteMaintenanceWarningConfirmAction $deleteMaintenanceWarningConfirmAction, MaintenanceWarning $maintenanceWarning): RedirectResponse|array
    {
        return $deleteMaintenanceWarningConfirmAction
            ->setSubject($maintenanceWarning)
            ->controller(
                $request,
                $this->generateUrl('admin_maintenance')
            );
    }
}
