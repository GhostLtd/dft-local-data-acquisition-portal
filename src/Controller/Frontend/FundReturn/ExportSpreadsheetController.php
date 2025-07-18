<?php

namespace App\Controller\Frontend\FundReturn;

use App\Entity\Enum\JobState;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Messenger\JobStatus;
use App\Messenger\Spreadsheet\SpreadsheetJob;
use App\Repository\FundReturn\FundReturnRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ExportSpreadsheetController extends AbstractController
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        #[Autowire(service: 'cache.spreadsheet_jobs')]
        protected CacheItemPoolInterface        $cache,
        protected MessageBusInterface           $messageBus, private readonly FundReturnRepository $fundReturnRepository,
    )
    {
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
        return new RedirectResponse($this->generateUrl($queueRoute, ['jobId' => $job->getId()]));
    }

    #[Route('/export-spreadsheet/{jobId}', name: 'app_fund_return_export_spreadsheet_queue')]
    public function queue(
        Request $request,
        string  $jobId,
    ): Response
    {
        $jobStatus = $this->getJobStatus($jobId);

        $downloadLink = null;
        if ($jobStatus && $jobStatus->getState() === JobState::COMPLETED) {
            $downloadRoute = $request->attributes->get('download_route') ?? 'app_fund_return_export_spreadsheet_download';
            $downloadLink = $this->generateUrl($downloadRoute, ['jobId' => $jobId]);
        }

        return $this->render('frontend/fund_return/export_spreadsheet.html.twig', [
            'jobStatus' => $jobStatus,
            'downloadLink' => $downloadLink,
        ]);
    }

    #[Route('/export-spreadsheet/{jobId}/download', name: 'app_fund_return_export_spreadsheet_download')]
    public function downloadSpreadsheet(string $jobId): Response
    {
        $jobStatus = $this->getJobStatus($jobId);

        if (!$jobStatus || $jobStatus->getState() !== JobState::COMPLETED) {
            throw new NotFoundHttpException('Job not completed');
        }

        $fundReturn = $this->fundReturnRepository->find($jobStatus->getContext()['fundReturnId'] ?? '');
        if (!$fundReturn || !$this->authorizationChecker->isGranted(Role::CAN_EXPORT_SPREADSHEET, $fundReturn)) {
            throw new AccessDeniedHttpException();
        }

        $spreadsheet = $this->getSpreadsheet($jobId);
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

    protected function getJobStatus(string $jobId): ?JobStatus
    {
        if (!preg_match('/^[A-Z0-9]{26}$/', $jobId)) {
            throw new NotFoundHttpException('Invalid job ID');
        }

        $jobStatus = null;
        $item = $this->cache->getItem("status-{$jobId}");

        if ($item->isHit()) {
            $jobStatus = $item->get();
        }

        return $jobStatus instanceof JobStatus ? $jobStatus : null;
    }

    protected function getSpreadsheet(string $jobId): ?string
    {
        $item = $this->cache->getItem("data-{$jobId}");

        if (!$item->isHit()) {
            return null;
        }

        return $item->get();
    }
}
