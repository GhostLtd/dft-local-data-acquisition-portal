<?php

namespace App\Messenger\DataExport;

use App\Entity\Enum\JobState;
use App\Messenger\JobCacheHelper;
use App\Messenger\JobStatus;
use App\Utility\CsvExporter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DataExportJobHandler
{
    public function __construct(
        protected CsvExporter     $csvExporter,
        protected JobCacheHelper  $jobCacheHelper,
        protected LoggerInterface $logger,
    ) {}

    public function __invoke(DataExportJob $job): void
    {
        $jobId = $job->getId();
        $year = $job->getYear();
        $quarter = $job->getQuarter();

        $this->jobCacheHelper->setJobStatus($jobId, new JobStatus(JobState::RUNNING));

        try {
            $zipData = $this->csvExporter->getZipData($year, $quarter);

            if ($zipData) {
                $this->jobCacheHelper->setJobCacheEntry($jobId, 'data', $zipData);

                // Generate filename for the download
                $filenamePrefix = 'crsts';
                if ($year !== null && $quarter !== null) {
                    $nextYear = substr(strval($year + 1), -2);
                    $filenamePrefix .= "_{$year}_{$nextYear}_Q{$quarter}";
                }
                $filenameSuffix = (new \DateTime())->format('Ymd_Hisv');
                $filename = "{$filenamePrefix}_{$filenameSuffix}.zip";

                $this->jobCacheHelper->setJobStatus(
                    $jobId,
                    new JobStatus(JobState::COMPLETED, context: ['filename' => $filename])
                );

                // Log successful job completion
                $this->logger->info('Data export job completed successfully', [
                    'jobId' => $jobId,
                    'filename' => $filename,
                    'year' => $year,
                    'quarter' => $quarter
                ]);
            } else {
                $errorMessage = 'Failed to generate zip file';
                $this->jobCacheHelper->setJobStatus($jobId, new JobStatus(JobState::FAILED, $errorMessage));

                // Log failure
                $this->logger->error($errorMessage, [
                    'jobId' => $jobId,
                    'error' => $errorMessage,
                    'year' => $year,
                    'quarter' => $quarter
                ]);
            }
        } catch (\Exception $e) {
            $this->jobCacheHelper->setJobStatus($jobId, new JobStatus(JobState::FAILED, $e->getMessage()));

            $this->logger->error('Data export job failed with exception', [
                'jobId' => $jobId,
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
                'year' => $year,
                'quarter' => $quarter,
            ]);
        }
    }
}
