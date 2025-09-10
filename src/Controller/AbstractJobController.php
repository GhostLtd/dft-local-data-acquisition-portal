<?php

namespace App\Controller;

use App\Entity\Enum\JobState;
use App\Messenger\JobCacheHelper;
use App\Messenger\JobStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class AbstractJobController extends AbstractController
{
    public function __construct(
        protected JobCacheHelper      $jobCacheHelper,
        protected MessageBusInterface $messageBus,
    ) {}

    protected function getJobStatus(string $jobId): ?JobStatus
    {
        if (!preg_match('/^[A-Z0-9]{26}$/', $jobId)) {
            throw new NotFoundHttpException('Invalid job ID');
        }

        $jobStatus = null;
        $cacheItem = $this->jobCacheHelper->getJobCacheItem($jobId, 'status');

        if ($cacheItem->isHit()) {
            $jobStatus = $cacheItem->get();
        }

        return $jobStatus instanceof JobStatus ? $jobStatus : null;
    }

    protected function getJobData(string $jobId): ?string
    {
        $cacheItem = $this->jobCacheHelper->getJobCacheItem($jobId, 'data');

        if (!$cacheItem->isHit()) {
            return null;
        }

        return $cacheItem->get();
    }

    protected function getCompletedJobData(string $jobId): string
    {
        $jobStatus = $this->getJobStatus($jobId);

        if (!$jobStatus || $jobStatus->getState() !== JobState::COMPLETED) {
            throw new NotFoundHttpException('Job not completed');
        }

        $data = $this->getJobData($jobId);
        if (!$data) {
            throw new NotFoundHttpException('Job data not found');
        }

        return $data;
    }
}
