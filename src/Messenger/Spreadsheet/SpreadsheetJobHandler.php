<?php

namespace App\Messenger\Spreadsheet;

use App\Entity\Enum\JobState;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Messenger\JobCacheHelper;
use App\Messenger\JobStatus;
use App\Repository\FundReturn\FundReturnRepository;
use App\Utility\SpreadsheetCreator\CrstsSpreadsheetCreator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Ulid;

#[AsMessageHandler]
class SpreadsheetJobHandler
{
    public function __construct(
        protected JobCacheHelper          $jobCacheHelper,
        protected FundReturnRepository    $fundReturnRepository,
        protected CrstsSpreadsheetCreator $spreadsheetCreator,
        protected SluggerInterface        $slugger,
        protected LoggerInterface         $logger,
    ) {}

    public function __invoke(SpreadsheetJob $job): void
    {
        $jobId = $job->getId();

        $this->jobCacheHelper->setJobStatus($jobId, new JobStatus(JobState::RUNNING));

        try {
            $fundReturn = $this->fundReturnRepository->findForSpreadsheetExport(Ulid::fromString($job->getFundReturnId()));

            if (!$fundReturn) {
                $errorMessage = "No such fund return: {$job->getFundReturnId()}";

                $this->jobCacheHelper->setJobStatus($jobId, new JobStatus(JobState::FAILED, $errorMessage));
                $this->logger->error($errorMessage);
                throw new UnrecoverableMessageHandlingException($errorMessage);
            }

            if (!$fundReturn instanceof CrstsFundReturn) {
                $errorMessage = "Unsupported fund return type: ".$fundReturn::class;

                $this->jobCacheHelper->setJobStatus($jobId, new JobStatus(JobState::FAILED, $errorMessage));
                $this->logger->error($errorMessage);
                throw new UnrecoverableMessageHandlingException($errorMessage);
            }

            $spreadsheetData = $this->getSpreadsheetData($fundReturn);

            if ($spreadsheetData) {
                $this->jobCacheHelper->setJobCacheEntry($jobId, 'data', $spreadsheetData);

                $filename = $this->getFilename($fundReturn);

                $this->jobCacheHelper->setJobStatus(
                    $jobId,
                    new JobStatus(JobState::COMPLETED, context: [
                        'filename' => $filename,
                        'fundReturnId' => strval($fundReturn->getId()),
                    ])
                );

                $this->logger->info('Spreadsheet job completed successfully', [
                    'filename' => $filename,
                    'fundReturnId' => strval($fundReturn->getId()),
                    'authority' => $fundReturn->getFundAward()->getAuthority()->getName(),
                    'year' => $fundReturn->getYear(),
                    'quarter' => $fundReturn->getQuarter()
                ]);
            } else {
                $errorMessage = 'Failed to generate spreadsheet data';
                $this->jobCacheHelper->setJobStatus($jobId, new JobStatus(JobState::FAILED, $errorMessage));

                $this->logger->error($errorMessage, [
                    'jobId' => $jobId,
                    'fundReturnId' => strval($fundReturn->getId()),
                    'authority' => $fundReturn->getFundAward()->getAuthority()->getName(),
                    'year' => $fundReturn->getYear(),
                    'quarter' => $fundReturn->getQuarter(),
                ]);
            }
        } catch (\Exception $e) {
            $this->jobCacheHelper->setJobStatus($jobId, new JobStatus(JobState::FAILED, $e->getMessage()));

            $this->logger->error('Spreadsheet job failed with exception', [
                'jobId' => $jobId,
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
                'fundReturnId' => $job->getFundReturnId(),
            ]);
        }
    }

    protected function getFilename(FundReturn $fundReturn): string
    {
        $authorityName = $this->slugger->slug($fundReturn->getFundAward()->getAuthority()->getName())->lower();
        $datePeriod = "{$fundReturn->getYear()}-{$fundReturn->getNextYearAsTwoDigits()}-Q{$fundReturn->getQuarter()}";
        $now = (new \DateTime())->format('Ymd_Hisv');
        return "{$datePeriod}_{$authorityName}_CRSTS_{$now}.xlsx";
    }

    public function getSpreadsheetData(CrstsFundReturn $fundReturn): ?string
    {
        $xlsx = $this->spreadsheetCreator->getSpreadsheetForFundReturn($fundReturn);
        $stream = fopen('php://memory', 'w+');
        $xlsx->save($stream);
        rewind($stream);
        $data = stream_get_contents($stream);
        fclose($stream);
        return $data === false ? null : $data;
    }
}
