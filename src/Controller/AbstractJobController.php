<?php

namespace App\Controller;

use App\Entity\Enum\JobState;
use App\Messenger\JobStatus;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class AbstractJobController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'cache.job_cache')]
        protected CacheItemPoolInterface $cache,
        protected MessageBusInterface $messageBus
    ) {}
    

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

    protected function getJobData(string $jobId): ?string
    {
        $item = $this->cache->getItem("data-{$jobId}");

        if (!$item->isHit()) {
            return null;
        }

        return $item->get();
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
