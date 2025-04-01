<?php

namespace App\Controller\Frontend\SchemeReturn;

use App\Controller\Frontend\AbstractReturnController;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use App\Utility\ConfirmAction\Frontend\SchemeReadyForSignOffConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Form\ConfirmActionType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fund-return/{fundReturnId}/scheme/{schemeId}', name: 'app_scheme_return_mark_as_')]
class ReadyForSignoffController extends AbstractReturnController
{
    public const string MARK_AS_READY = 'mark_as_ready';
    public const string MARK_AS_NOT_READY = 'mark_as_not_ready';

    #[Route('/mark-as-ready-for-signoff', name: 'ready_for_signoff', defaults: ['type' => self::MARK_AS_READY])]
    #[Route('/mark-as-not-ready-for-signoff', name: 'not_ready_for_signoff', defaults: ['type' => self::MARK_AS_NOT_READY])]
    #[Template("frontend/scheme_return/ready_for_signoff.html.twig")]
    public function schemeReadyForSignoff(
        #[MapEntity(expr: 'repository.findForDashboard(fundReturnId)')]
        FundReturn $fundReturn,
        #[MapEntity(expr: 'repository.findForDashboard(schemeId)')]
        Scheme     $scheme,
        Request    $request,
        SchemeReadyForSignOffConfirmAction $confirmAction,
        DashboardLinksBuilder $linksBuilder,
        string $type,
    ): RedirectResponse | array
    {
        $schemeReturn = $fundReturn->getSchemeReturnForScheme($scheme);
        $role = match($type) {
            self::MARK_AS_READY => Role::CAN_MARK_SCHEME_RETURN_AS_READY,
            self::MARK_AS_NOT_READY => Role::CAN_MARK_SCHEME_RETURN_AS_NOT_READY,
        };
        $this->denyAccessUnlessGranted($role, $schemeReturn);
        match($type) {
            self::MARK_AS_READY => $linksBuilder->setAtSchemeReadyForSignoff($fundReturn, $scheme),
            self::MARK_AS_NOT_READY => $linksBuilder->setAtSchemeNotReadyForSignoff($fundReturn, $scheme),
        };

        return $confirmAction
            ->setSubject($schemeReturn)
            ->setType($type)
            ->setExtraViewData([
                'linksBuilder' => $linksBuilder,
                'fundReturn' => $fundReturn,
                'schemeReturn' => $schemeReturn,
            ])
            ->controller($request, $this->getActionUrl($fundReturn->getId(), $scheme->getId()));
    }


    protected function getActionUrl(string $fundReturnId, string $schemeId): string
    {
        return $this->generateUrl('app_scheme_return', [
            'fundReturnId' => $fundReturnId,
            'schemeId' => $schemeId
        ]);
    }
}
