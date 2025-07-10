<?php

namespace App\Controller\Admin\SchemeReturn;

use App\Controller\Frontend\AbstractReturnController;
use App\Entity\Enum\Role;
use App\Entity\Enum\SchemeLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
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

class EditMilestoneBaselinesController extends AbstractReturnController
{
    #[Route('/fund-return/{fundReturnId}/scheme/{schemeId}/milestone-baselines', name: 'admin_scheme_return_milestone_baselines_edit')]
    #[IsGranted(Role::CAN_EDIT_BASELINES, 'fundReturn')]
    public function fundReturnBaselines(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn                                             $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeId)')]
        Scheme                                                 $scheme,
        Request                                                $request,
        DashboardLinksBuilder $linksBuilder,
    ): Response
    {
        $linksBuilder->setAtSchemeMilestonesBaselinesEdit($fundReturn, $scheme);

        return $this->render('admin/scheme_return/milestone_baselines_edit.html.twig', [
            'linksBuilder' => $linksBuilder,
            'scheme' => $scheme,
//            'form' => $form,
        ]);
    }
}
