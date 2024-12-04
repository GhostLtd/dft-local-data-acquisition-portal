<?php

namespace App\Controller\Frontend;

use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\FundLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Form\FundReturn\Crsts\ExpensesType;
use App\Utility\CrstsHelper;
use App\Event\FundReturnSectionUpdateEvent;
use App\Repository\ProjectFund\ProjectFundRepository;
use App\Utility\Breadcrumb\Frontend\DashboardBreadcrumbBuilder;
use App\Utility\ExpensesTableHelper;
use App\Utility\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FundReturnController extends AbstractController
{
    #[Route('/fund-return/{fundReturnId}', name: 'app_fund_return')]
    public function fundReturn(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        ProjectFundRepository      $projectFundRepository,
    ): Response
    {
        $breadcrumbBuilder->setAtFundReturn($fundReturn);
        $fund = $fundReturn->getFund();

        // We get the projectFunds from this direction, so that we can list all of them and explicitly any that
        // do not requiring a return, if that is the case (e.g. CRSTS - if not retained and not quarter 1)

        // (Fetching via fundReturn->getProjectReturns() direction would only fetch those projects that
        //  do have returns, resulting in an incomplete list)
        $projectFunds = $projectFundRepository->getProjectFundsForRecipient(
            $fundReturn->getFundAward()->getRecipient(),
            $fund
        );

        return $this->render('frontend/fund_return.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'expenseDivisions' => $fundReturn->getExpenseDivisionConfigurations(),
            'fundLevelSections' => FundLevelSection::filterForFund($fund),
            'fundReturn' => $fundReturn,
            'projectFunds' => $projectFunds
        ]);
    }

    #[Route('/fund-return/{fundReturnId}/section/{section}', name: 'app_fund_return_edit')]
    public function fundReturnEdit(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        EntityManagerInterface     $entityManager,
        EventDispatcherInterface   $eventDispatcher,
        FundLevelSection           $section,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        Request                    $request,
    ): Response
    {
        $formClass = $section::getFormClassForFundAndSection($fundReturn->getFund(), $section);

        if (!$formClass) {
            throw new NotFoundHttpException();
        }

        $breadcrumbBuilder->setAtFundReturnSectionEdit($fundReturn, $section);
        $cancelUrl = $this->generateUrl('app_fund_return', ['fundReturnId' => $fundReturn->getId()]);

        $form = $this->createForm($formClass, $fundReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $fundReturn->getStatusForSection($section),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = FormHelper::whichButtonClicked($form);
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


    #[Route('/fund-return/{fundReturnId}/expense/{divisionSlug}', name: 'app_fund_return_expense_edit')]
    public function fundReturnExpense(
        DashboardBreadcrumbBuilder $breadcrumbBuilder,
        EntityManagerInterface     $entityManager,
        EventDispatcherInterface   $eventDispatcher,
        string                     $divisionSlug,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                 $fundReturn,
        Request                    $request,
        ExpensesTableHelper        $tableHelper,
    ): Response
    {
        $divisionConfiguration = $fundReturn->findExpenseDivisionConfigurationBySlug($divisionSlug);

        if (!$divisionConfiguration) {
            throw new NotFoundHttpException();
        }

        $breadcrumbBuilder->setAtFundReturnExpenseEdit($fundReturn, $divisionConfiguration);
        $cancelUrl = $this->generateUrl('app_fund_return', ['fundReturnId' => $fundReturn->getId()]);

        $expensesTableHelper = $tableHelper
            ->setDivisionConfiguration($divisionConfiguration)
            ->setRowGroupConfigurations(CrstsHelper::getExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        $form = $this->createForm(ExpensesType::class, $fundReturn, [
            'cancel_url' => $cancelUrl,
            'completion_status' => $fundReturn->getStatusForSection($divisionConfiguration),
            'expenses_table_helper' => $expensesTableHelper,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clickedButton = FormHelper::whichButtonClicked($form);
            if ($clickedButton) {
                $eventDispatcher->dispatch(new FundReturnSectionUpdateEvent($fundReturn, $divisionConfiguration, ['mode' => $clickedButton]));
                $entityManager->flush();
                return new RedirectResponse($cancelUrl);
            }
        }

        return $this->render('frontend/fund_return_expenses_edit.html.twig', [
            'breadcrumbBuilder' => $breadcrumbBuilder,
            'expensesTable' => $expensesTableHelper->getTableRows(),
            'form' => $form,
        ]);
    }
}
