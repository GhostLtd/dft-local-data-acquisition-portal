<?php

namespace App\Messenger\Spreadsheet;

use App\Entity\Enum\JobState;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Messenger\JobStatus;
use App\Repository\FundReturn\FundReturnRepository;
use App\Utility\SpreadsheetCreator\CrstsSpreadsheetCreator;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Ulid;

#[AsMessageHandler]
class SpreadsheetJobHandler
{
    public function __construct(
        #[Autowire(service: 'cache.spreadsheet_jobs')]
        protected CacheItemPoolInterface  $cache,
        protected FundReturnRepository    $fundReturnRepository,
        protected CrstsSpreadsheetCreator $spreadsheetCreator,
        protected SluggerInterface        $slugger,
    ) {}

    public function __invoke(SpreadsheetJob $job): void
    {
        $jobId = $job->getId();
        $fundReturn = $this->fundReturnRepository->findForSpreadsheetExport(Ulid::fromString($job->getFundReturnId()));

        if (!$fundReturn) {
            throw new UnrecoverableMessageHandlingException("No such fund return: {$job->getFundReturnId()}");
        }

        if (!$fundReturn instanceof CrstsFundReturn) {
            throw new UnrecoverableMessageHandlingException("Unsupported fund return type: ".$fundReturn::class);
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

            $jobStatus->set(new JobStatus(JobState::COMPLETED, context: [
                'filename' => $this->getFilename($fundReturn),
                'fundReturnId' => strval($fundReturn->getId()),
            ]));
        } else {
            $jobStatus->set(new JobStatus(JobState::FAILED));
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
