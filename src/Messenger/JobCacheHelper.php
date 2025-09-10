<?php

namespace App\Messenger;

use App\Entity\Enum\JobState;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class JobCacheHelper
{
    public function __construct(
        #[Autowire(service: 'cache.job_cache')]
        protected CacheItemPoolInterface $cache,
    ) {}

    public function createJobStatus(string $jobId): JobStatus
    {
        $cacheEntry = $this->getJobCacheItem($jobId, 'status');
        $jobStatus = new JobStatus(JobState::NEW);
        $cacheEntry->set($jobStatus);
        $this->cache->save($cacheEntry);

        return $jobStatus;
    }

    public function setJobStatus(string $jobId, JobStatus $jobStatus): void
    {
        $this->setJobCacheEntry($jobId, 'status', $jobStatus);
    }

    public function setJobCacheEntry(string $jobId, string $type, mixed $data): void
    {
        $cacheEntry = $this->getJobCacheItem($jobId, $type);
        $cacheEntry->set($data);
        $this->cache->save($cacheEntry);
    }

    public function getJobCacheItem(string $jobId, string $type): CacheItemInterface
    {
        $statusKey = "job-{$type}-{$jobId}";

        try {
            $jobStatus = $this->cache->getItem($statusKey);
        } catch (InvalidArgumentException) {
            throw new \RuntimeException("Failed to get job cache item of type '{$type}' for job id {$jobId}");
        }

        return $jobStatus;
    }
}
