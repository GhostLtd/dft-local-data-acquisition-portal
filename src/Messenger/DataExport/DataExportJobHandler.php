<?php

namespace App\Messenger\DataExport;

use App\Entity\Enum\JobState;
use App\Messenger\JobStatus;
use App\Utility\CsvExporter;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DataExportJobHandler
{
    public function __construct(
        #[Autowire(service: 'cache.job_cache')]
        protected CacheItemPoolInterface $cache,
        protected CsvExporter $csvExporter,
        protected LoggerInterface $logger,
    ) {}

    public function __invoke(DataExportJob $job): void
    {
        $jobId = $job->getId();
        $year = $job->getYear();
        $quarter = $job->getQuarter();

        $statusKey = "status-{$jobId}";

        $jobStatus = $this->cache->getItem($statusKey);
        $jobStatus->set(new JobStatus(JobState::RUNNING));
        $this->cache->save($jobStatus);

        try {
            $zipData = $this->csvExporter->getZipData($year, $quarter);

            if ($zipData) {
                $jobData = $this->cache->getItem("data-{$jobId}");
                $jobData->set($zipData);
                $this->cache->save($jobData);

                // Generate filename for the download
                $filenamePrefix = 'crsts';
                if ($year !== null && $quarter !== null) {
                    $nextYear = substr(strval($year + 1), - 2);
                    $filenamePrefix .= "_{$year}_{$nextYear}_Q{$quarter}";
                }
                $filenameSuffix = (new \DateTime())->format('Ymd_Hisv');
                $filename = "{$filenamePrefix}_{$filenameSuffix}.zip";

                $jobStatus->set(new JobStatus(JobState::COMPLETED, context: [
                    'filename' => $filename,
                ]));
                
                // Log successful job completion
                $this->logger->info('Data export job completed successfully', [
                    'jobId' => $jobId,
                    'filename' => $filename,
                    'year' => $year,
                    'quarter' => $quarter
                ]);
            } else {
                $errorMessage = 'Failed to generate zip file';
                $jobStatus->set(new JobStatus(JobState::FAILED, $errorMessage));
                
                // Log failure
                $this->logger->error($errorMessage, [
                    'jobId' => $jobId,
                    'error' => $errorMessage,
                    'year' => $year,
                    'quarter' => $quarter
                ]);
            }
        } catch (\Exception $e) {
            // Log exception with stack trace
            $this->logger->error('Data export job failed with exception', [
                'jobId' => $jobId,
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
                'year' => $year,
                'quarter' => $quarter,
            ]);
            
            $jobStatus->set(new JobStatus(JobState::FAILED, $e->getMessage()));
        }

        $this->cache->save($jobStatus);
    }
}