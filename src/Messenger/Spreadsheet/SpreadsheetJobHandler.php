<?php

namespace App\Messenger\Spreadsheet;

use App\Entity\Enum\JobState;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Messenger\JobStatus;
use App\Repository\FundReturn\FundReturnRepository;
use App\Utility\SpreadsheetCreator\CrstsSpreadsheetCreator;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Ulid;

#[AsMessageHandler]
class SpreadsheetJobHandler
{
    public function __construct(
        #[Autowire(service: 'cache.job_cache')]
        protected CacheItemPoolInterface  $cache,
        protected FundReturnRepository    $fundReturnRepository,
        protected CrstsSpreadsheetCreator $spreadsheetCreator,
        protected SluggerInterface        $slugger,
        protected LoggerInterface         $logger,
    ) {}

    public function __invoke(SpreadsheetJob $job): void
    {
        $jobId = $job->getId();
        
        try {
            $fundReturn = $this->fundReturnRepository->findForSpreadsheetExport(Ulid::fromString($job->getFundReturnId()));

            if (!$fundReturn) {
                $errorMessage = "No such fund return: {$job->getFundReturnId()}";
                $this->logger->error($errorMessage);
                throw new UnrecoverableMessageHandlingException($errorMessage);
            }

            if (!$fundReturn instanceof CrstsFundReturn) {
                $errorMessage = "Unsupported fund return type: ".$fundReturn::class;
                $this->logger->error($errorMessage);
                throw new UnrecoverableMessageHandlingException($errorMessage);
            }

            $statusKey = "status-{$jobId}";

            $jobStatus = $this->cache->getItem($statusKey);
            $jobStatus->set(new JobStatus(JobState::RUNNING));
            $this->cache->save($jobStatus);

            $spreadsheetData = $this->getSpreadsheetData($fundReturn);

            if ($spreadsheetData) {
                $jobData = $this->cache->getItem("data-{$jobId}");
                $jobData->set($spreadsheetData);
                $this->cache->save($jobData);

                $filename = $this->getFilename($fundReturn);
                
                $jobStatus->set(new JobStatus(JobState::COMPLETED, context: [
                    'filename' => $filename,
                    'fundReturnId' => strval($fundReturn->getId()),
                ]));
                
                // Log successful job completion
                $this->logger->info('Spreadsheet job completed successfully', [
                    'filename' => $filename,
                    'fundReturnId' => strval($fundReturn->getId()),
                    'authority' => $fundReturn->getFundAward()->getAuthority()->getName(),
                    'year' => $fundReturn->getYear(),
                    'quarter' => $fundReturn->getQuarter()
                ]);
            } else {
                $errorMessage = 'Failed to generate spreadsheet data';
                $jobStatus->set(new JobStatus(JobState::FAILED, $errorMessage));
                
                // Log failure
                $this->logger->error($errorMessage, [
                    'jobId' => $jobId,
                    'fundReturnId' => strval($fundReturn->getId()),
                    'authority' => $fundReturn->getFundAward()->getAuthority()->getName(),
                    'year' => $fundReturn->getYear(),
                    'quarter' => $fundReturn->getQuarter(),
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Spreadsheet job failed with exception', [
                'jobId' => $jobId,
                'error' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
                'fundReturnId' => $job->getFundReturnId(),
            ]);
            
            $jobStatus = $this->cache->getItem("status-{$jobId}");
            $jobStatus->set(new JobStatus(JobState::FAILED, $e->getMessage()));
        }

        $this->cache->save($jobStatus);
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
