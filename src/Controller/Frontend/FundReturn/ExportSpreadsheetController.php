<?php

namespace App\Controller\Frontend\FundReturn;

use App\Controller\AbstractJobController;
use App\Entity\Enum\JobState;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Messenger\Spreadsheet\SpreadsheetJob;
use App\Repository\FundReturn\FundReturnRepository;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ExportSpreadsheetController extends AbstractJobController
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        #[Autowire(service: 'cache.job_cache')]
        CacheItemPoolInterface                  $cache,
        MessageBusInterface                     $messageBus,
        protected FundReturnRepository          $fundReturnRepository,
    ) {
        parent::__construct($cache, $messageBus);
    }

    #[Route('/fund-return/{fundReturnId}/export-spreadsheet', name: 'app_fund_return_export_spreadsheet')]
    #[IsGranted(Role::CAN_EXPORT_SPREADSHEET, 'fundReturn')]
    public function exportSpreadsheet(
        #[MapEntity(expr: 'repository.find(fundReturnId)')]
        FundReturn $fundReturn,
        Request    $request,
    ): Response
    {
        $job = new SpreadsheetJob($fundReturn);
        $this->messageBus->dispatch($job);

        $queueRoute = $request->attributes->get('queue_route') ?? 'app_fund_return_export_spreadsheet_queue';
        return new RedirectResponse($this->generateUrl($queueRoute, ['fundReturnId' => $fundReturn->getId(), 'jobId' => $job->getId()]));
    }

    #[Route('/fund-return/{fundReturnId}/export-spreadsheet/{jobId}', name: 'app_fund_return_export_spreadsheet_queue')]
    #[IsGranted(Role::CAN_EXPORT_SPREADSHEET, 'fundReturn')]
    public function queue(
        DashboardLinksBuilder $linksBuilder,
        #[MapEntity(expr: 'repository.find(fundReturnId)')]
        FundReturn            $fundReturn,
        Request               $request,
        string                $jobId,
    ): Response
    {
        $jobStatus = $this->getJobStatus($jobId);
        $linksBuilder->setAtSpreadsheetExport($fundReturn);

        $downloadUrl = null;
        $redirectUrl = null;
        if ($jobStatus && $jobStatus->getState() === JobState::COMPLETED) {
            $downloadRoute = $request->attributes->get('download_route') ?? 'app_fund_return_export_spreadsheet_download';
            $downloadUrl = $this->generateUrl($downloadRoute, ['fundReturnId' => $fundReturn->getId(), 'jobId' => $jobId]);

            $redirectRoute = $request->attributes->get('redirect_route') ?? 'app_fund_return';
            $redirectUrl = $this->generateUrl($redirectRoute, ['fundReturnId' => $fundReturn->getId()]);
        }

        return $this->render('frontend/fund_return/export_spreadsheet.html.twig', [
            'jobStatus' => $jobStatus,
            'linksBuilder' => $linksBuilder,
            'downloadUrl' => $downloadUrl, // URL to hit for the download...
            'redirectUrl' => $redirectUrl, // Where to redirect afterwards...
        ]);
    }

    #[Route('/fund-return/{fundReturnId}/export-spreadsheet/{jobId}/download', name: 'app_fund_return_export_spreadsheet_download')]
    #[IsGranted(Role::CAN_EXPORT_SPREADSHEET, 'fundReturn')]
    public function download(
        #[MapEntity(expr: 'repository.find(fundReturnId)')]
        FundReturn $fundReturn,
        string     $jobId
    ): Response
    {
        $spreadsheet = $this->getCompletedJobData($jobId);
        $jobStatus = $this->getJobStatus($jobId);

        $headers = [
            'content-type' => 'application/vnd.ms-excel',
            'content-length' => strlen($spreadsheet),
        ];

        $filename = $jobStatus->getContext()['filename'] ?? null;
        if ($filename) {
            $headers['content-disposition'] = "attachment; filename={$filename}";
        }

        return new Response($spreadsheet, 200, $headers);
    }

}
