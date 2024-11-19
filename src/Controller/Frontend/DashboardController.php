<?php

namespace App\Controller\Frontend;

use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\FundLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\User;
use App\Event\FundReturnSectionUpdateEvent;
use App\Form\ReturnBaseType;
use App\Repository\MaintenanceWarningRepository;
use App\Repository\RecipientRepository;
use App\Utility\Breadcrumb\Frontend\DashboardBreadcrumbBuilder;
use App\Utility\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        DashboardBreadcrumbBuilder   $breadcrumbBuilder,
        MaintenanceWarningRepository $maintenanceWarningRepository,
        RecipientRepository          $recipientRepository,
        UserInterface                $user,
    ): Response
    {
        if (!$user instanceof User) {
            throw new NotFoundHttpException();
        }

        return $this->render('frontend/dashboard.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'maintenanceWarningBanner' => $maintenanceWarningRepository->getNotificationBanner(),
            'recipients' => $recipientRepository->getRecipientsFundAwardsAndReturnsForUser($user),
        ]);
    }

    #[Route('/fund-return/{id}', name: 'app_fund_return')]
    public function fundReturn(
        #[MapEntity(expr: 'repository.findForDashboard(id)')]
        FundReturn                 $fundReturn,
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
    ): Response
    {
        $breadcrumbBuilder->setAtFundReturn($fundReturn);

        return $this->render('frontend/fund_return.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'fundReturn' => $fundReturn,
            'fundLevelSections' => FundLevelSection::cases(),
            'fundLevelExpenses' => ExpenseType::filterForFund(),
        ]);
    }

    #[Route('/fund-return/{id}/section/{section}', name: 'app_fund_return_edit')]
    public function fundReturnEdit(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        EntityManagerInterface     $entityManager,
        EventDispatcherInterface   $eventDispatcher,
        FundLevelSection           $section,
        FundReturn                 $fundReturn,
        Request                    $request,
    ): Response
    {
        $formClass = $section::getFormClassForFundAndSection($fundReturn->getFund(), $section);

        if (!$formClass) {
            throw new NotFoundHttpException();
        }

        $breadcrumbBuilder->setAtFundReturnEdit($fundReturn, $section);
        $cancelUrl = $this->generateUrl('app_fund_return', ['id' => $fundReturn->getId()]);

        $form = $this->createForm($formClass, $fundReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $fundReturn->getStatusForSection($section),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = FormHelper::whichButtonClicked($form, [ReturnBaseType::SAVE, ReturnBaseType::MARK_AS_COMPLETED, ReturnBaseType::MARK_AS_IN_PROGRESS]);
            if ($clickedButton) {
                $eventDispatcher->dispatch(new FundReturnSectionUpdateEvent($fundReturn, $section, ['mode' => $clickedButton]));
                $entityManager->flush();
                return new RedirectResponse($cancelUrl);
            }
        }

        return $this->render('frontend/fund_return_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'form' => $form,
            'fundReturn' => $fundReturn,
            'section' => $section,
        ]);
    }
}
