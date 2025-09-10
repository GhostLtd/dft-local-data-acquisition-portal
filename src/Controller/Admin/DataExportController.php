<?php

namespace App\Controller\Admin;

use App\Controller\AbstractJobController;
use App\Entity\Enum\JobState;
use App\Form\Type\Admin\DataExportType;
use App\Messenger\DataExport\DataExportJob;
use App\Messenger\JobCacheHelper;
use App\Utility\Breadcrumb\Admin\DashboardLinksBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/data-export')]
class DataExportController extends AbstractJobController
{
    public function __construct(
        JobCacheHelper      $jobCacheHelper,
        MessageBusInterface $messageBus,
    ) {
        parent::__construct($jobCacheHelper, $messageBus);
    }

    #[Route(path: '', name: 'admin_data_export')]
    public function dataExport(
        DashboardLinksBuilder $linksBuilder,
        Request               $request,
    ): Response
    {
        $linksBuilder->setNavLinks(null);
        $form = $this->createForm(DataExportType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $yearAndQuarter = $form->getData()['year_and_quarter'] ?? null;

            $year = null;
            $quarter = null;
            if ($yearAndQuarter) {
                if (preg_match('/^(\d{4})-(\d{1})$/', $yearAndQuarter, $matches)) {
                    $year = intval($matches[1]);
                    $quarter = intval($matches[2]);
                }
            }

            $job = new DataExportJob($year, $quarter);
            $this->messageBus->dispatch($job);

            return new RedirectResponse($this->generateUrl('admin_data_export_queue', ['jobId' => $job->getId()]));
        }

        return $this->render('admin/data_export/index.html.twig', [
            'linksBuilder' => $linksBuilder,
            'form' => $form,
        ]);
    }

    #[Route(path: '/queue/{jobId}', name: 'admin_data_export_queue')]
    public function queue(
        DashboardLinksBuilder $linksBuilder,
        string                $jobId,
    ): Response
    {
        $jobStatus = $this->getJobStatus($jobId);
        $linksBuilder->setNavLinks(null);

        $downloadUrl = null;
        $redirectUrl = null;

        if (!$jobStatus) {
            throw new NotFoundHttpException();
        }

        if ($jobStatus->getState() === JobState::COMPLETED) {
            $downloadUrl = $this->generateUrl('admin_data_export_download', ['jobId' => $jobId]);
            $redirectUrl = $this->generateUrl('admin_data_export');
        }

        return $this->render('admin/data_export/queue.html.twig', [
            'jobStatus' => $jobStatus,
            'linksBuilder' => $linksBuilder,
            'downloadUrl' => $downloadUrl,
            'redirectUrl' => $redirectUrl,
        ]);
    }

    #[Route(path: '/download/{jobId}', name: 'admin_data_export_download')]
    public function download(string $jobId): Response
    {
        $zipData = $this->getCompletedJobData($jobId);
        $jobStatus = $this->getJobStatus($jobId);

        $headers = [
            'content-type' => 'application/zip',
            'content-length' => strlen($zipData),
        ];

        $filename = $jobStatus->getContext()['filename'] ?? 'data_export.zip';
        $headers['content-disposition'] = "attachment; filename={$filename}";

        return new Response($zipData, 200, $headers);
    }
}
