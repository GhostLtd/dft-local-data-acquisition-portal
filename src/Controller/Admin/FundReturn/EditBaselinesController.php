<?php

namespace App\Controller\Admin\FundReturn;

use App\Controller\Frontend\AbstractReturnController;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Form\Type\FundReturn\Crsts\ExpensesType;
use App\Utility\Breadcrumb\Admin\DashboardLinksBuilder;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EditBaselinesController extends AbstractReturnController
{
    #[Route('/fund-return/{fundReturnId}/baselines/{divisionKey}', name: 'admin_fund_return_baselines_edit')]
    #[IsGranted(Role::CAN_EDIT_BASELINES, 'fundReturn')]
    public function fundReturnBaselines(
        DashboardLinksBuilder $linksBuilder,
        string                $divisionKey,
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn            $fundReturn,
        Request               $request,
        ExpensesTableHelper   $tableHelper,
    ): Response
    {
        $tableHelper
            ->setConfiguration(CrstsHelper::getFundBaselinesTable($fundReturn->getYear(), $fundReturn->getQuarter()))
            ->setDivisionKey($divisionKey)
            ->setEditableBaselines(true);

        $divisionConfiguration = $tableHelper->getDivisionConfiguration();
        if (!$divisionConfiguration) {
            throw new NotFoundHttpException();
        }

        $linksBuilder->setAtFundReturnBaselinesEdit($fundReturn, $divisionConfiguration);
        $cancelUrl = $this->generateUrl('admin_fund_return', [
            'fundReturnId' => $fundReturn->getId()
        ])."#baselines-{$divisionKey}";


        $form = $this->createForm(ExpensesType::class, $fundReturn, [
            'cancel_url' => $cancelUrl,
            'comments_enabled' => false,
            'expenses_table_helper' => $tableHelper,
        ]);

        if ($response = $this->processForm($form, $request, $cancelUrl)) {
            return $response;
        }

        return $this->render('admin/fund_return/baselines_edit.html.twig', [
            'linksBuilder' => $linksBuilder,
            'expensesTable' => $tableHelper->getTable(),
            'form' => $form,
        ]);
    }
}
