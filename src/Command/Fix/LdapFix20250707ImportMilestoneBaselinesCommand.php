<?php

namespace App\Command\Fix;

use App\Entity\Enum\MilestoneType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Milestone;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ldap:fix:20250707-import-milestone-baselines',
    description: 'Import milestone baselines',
)]
class LdapFix20250707ImportMilestoneBaselinesCommand extends AbstractSheetBasedCommand
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('import-path', InputArgument::REQUIRED, 'Path to the XLS source files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $importPath = $input->getArgument('import-path');

        $io->writeln('<info>Importing milestone baselines</info>');

        $hasErrors = false;

        $latestYear = null;
        $latestQuarter = null;

        // Set milestone baseline history from source files
        $sourcesByYear = $this->getSourcesByYear($io, $importPath);
        foreach(array_keys($sourcesByYear) as $year) {
            $latestYear = $year;
            foreach($sourcesByYear[$year] as $quarter => $path) {
                $latestQuarter = $quarter;
                $hasErrors |= $this->import($io, $year, $quarter, $path);
            }
        }

        if (!$hasErrors && $latestYear && $latestQuarter) {
            $io->writeln('<info>Propagating those milestones to later returns</info>');
            $this->propagateMilestonesToLaterReturns($latestYear, $latestQuarter);
        }

        if ($hasErrors) {
            $io->error("Exiting without saving");
            return Command::FAILURE;
        } else {
            $io->success("Saving...");
            $this->entityManager->flush();
            return Command::SUCCESS;
        }
    }

    protected function import(SymfonyStyle $io, int $year, int $quarter, string $importPath): bool
    {
        $reader = IOFactory::createReaderForFile($importPath)
            ->setLoadSheetsOnly(['Milestone'])
            ->setReadDataOnly(true);

        $spreadsheet = $reader->load($importPath);
        $sheet = $spreadsheet->getSheetByName('Milestone');

        $nnTrim = fn(?string $v) => $v === null ? null : trim($v);

        $io->writeln("- Q{$quarter} {$year}");

        $byMcaAndScheme = [];
        $hasErrors = false;

        foreach($sheet->getRowIterator(2) as $row) {
            $rowIndex = $row->getRowIndex();

            $date = $sheet->getCell([1, $rowIndex])->getValue();

            if ($date > 2000 && $date < 2050) {
                $date = new \DateTime("{$date}-01-01");
            } else {
                $date = $date === null ? null : Date::excelToDateTimeObject($date);
            }

            $isBaseline = $sheet->getCell([2, $rowIndex])->getValue();
            $milestoneType = $sheet->getCell([3, $rowIndex])->getValue();

            if ($isBaseline === 'N') {
                $milestoneType = match($milestoneType) {
                    'Start construction' => MilestoneType::START_CONSTRUCTION,
                    'End construction' => MilestoneType::END_CONSTRUCTION,
                    'Start development' => MilestoneType::START_DEVELOPMENT,
                    'End development' => MilestoneType::END_DEVELOPMENT,
                };
            } else if ($isBaseline === 'Y') {
                $milestoneType = match($milestoneType) {
                    'Start construction' => MilestoneType::BASELINE_START_CONSTRUCTION,
                    'End construction' => MilestoneType::BASELINE_END_CONSTRUCTION,
                    'Start development' => MilestoneType::BASELINE_START_DEVELOPMENT,
                    'End development' => MilestoneType::BASELINE_END_DEVELOPMENT,
                };
            } else {
                $io->error("Invalid \$isBaseline value: {$isBaseline}");
                $hasErrors = true;
                continue;
            }

            $location = $nnTrim($sheet->getCell([4, $rowIndex])->getValue());

            [$schemeName, $authorityName] = explode("_", $location);
            $schemeName = $nnTrim($schemeName);
            $authorityName = $this->convertAuthorityName($nnTrim($authorityName));

            $byMcaAndScheme[$authorityName] ??= [];
            $byMcaAndScheme[$authorityName][$schemeName] ??= [];
            $byMcaAndScheme[$authorityName][$schemeName][$milestoneType->value] = (new Milestone())
                ->setType($milestoneType)
                ->setDate($date);
        }

        foreach($byMcaAndScheme as $authorityName => $schemes) {
            /** @var CrstsSchemeReturn[] $schemeReturns */
            $schemeReturns = $this->entityManager
                ->getRepository(CrstsSchemeReturn::class)
                ->createQueryBuilder('csr')
                ->select('fr, csr, m, s')
                ->join('csr.fundReturn', 'fr')
                ->join('fr.fundAward', 'fa')
                ->join('fa.authority', 'a')
                ->join('csr.milestones', 'm')
                ->join('csr.scheme', 's')
                ->where('fr.year = :year')
                ->andWhere('fr.quarter = :quarter')
                ->andWhere('a.name = :authorityName')
                ->setParameter('year', $year)
                ->setParameter('quarter', $quarter)
                ->setParameter('authorityName', $authorityName)
                ->getQuery()
                ->getResult();

            foreach($schemes as $schemeName => $milestones) {
                $schemeReturn = $this->getSchemeReturnBySchemeName($schemeReturns, $schemeName);

                if (!$schemeReturn) {
                    $io->error("Cannot find scheme with name: {$schemeName}");
                    continue;
                }

                // Check existing dates match...
                /** @var Milestone $milestone */
                foreach($milestones as $milestone) {
                    $milestoneType = $milestone->getType();
                    if ($milestoneType->isBaselineMilestone()) {
                        continue;
                    }

                    $schemeMilestone = $schemeReturn->getMilestoneByType($milestoneType);
                    if (!$schemeMilestone) {
                        $io->error("Cannot find milestone matching: {$milestoneType->value}");
                        $hasErrors = true;
                        continue;
                    }

                    if ($schemeMilestone->getDate() != $milestone->getDate()) {
                        $io->error("Date mismatch in data on: {$schemeName}, {$milestoneType->value}");
                        $hasErrors = true;
                    }
                }

                /** @var Milestone $milestone */
                foreach($milestones as $milestone) {
                    $milestoneType = $milestone->getType();
                    if (!$milestoneType->isBaselineMilestone()) {
                        continue;
                    }

                    $schemeMilestone = $schemeReturn->getMilestoneByType($milestone->getType());
                    if ($schemeMilestone) {
                        $io->error("Baseline milestone already exists: {$schemeName}, {$milestoneType->value}");
                        $hasErrors = true;
                        continue;
                    }

                    $schemeReturn->addMilestone($milestone);
                }
            }
        }

        return $hasErrors;
    }

    protected function propagateMilestonesToLaterReturns(int $sourceYear, int $sourceQuarter): void
    {
        /** @var CrstsFundReturn[] $sourceFundReturns */
        $sourceFundReturns = $this->entityManager
            ->getRepository(CrstsFundReturn::class)
            ->createQueryBuilder('cfr')
            ->select('cfr, fa, a')
            ->join('cfr.fundAward', 'fa')
            ->join('fa.authority', 'a')
            ->where('cfr.year = :year')
            ->andWhere('cfr.quarter = :quarter')
            ->getQuery()
            ->setParameter('year', $sourceYear)
            ->setParameter('quarter', $sourceQuarter)
            ->getResult();

        foreach($sourceFundReturns as $sourceFundReturn) {
            $fundAwardId = $sourceFundReturn->getFundAward()->getId();

            /** @var CrstsSchemeReturn[] $sourceSchemeReturns */
            $sourceSchemeReturns = $this->entityManager
                ->getRepository(CrstsSchemeReturn::class)
                ->createQueryBuilder('csr')
                ->select('csr, s')
                ->join('csr.fundReturn', 'fr')
                ->join('csr.scheme', 's')
                ->where('fr.id = :fundReturnId')
                ->getQuery()
                ->setParameter('fundReturnId', $sourceFundReturn->getId(), UlidType::NAME)
                ->getResult();

            /** @var CrstsSchemeReturn[] $targetSchemeReturns */
            $targetSchemeReturns = $this->entityManager
                ->getRepository(CrstsSchemeReturn::class)
                ->createQueryBuilder('csr')
                ->select('csr, fr, fa')
                ->join('csr.fundReturn', 'fr')
                ->join('fr.fundAward', 'fa')
                ->where('fa.id = :fundAwardId')
                ->andWhere('(fr.year > :year OR (fr.year = :year AND fr.quarter > :quarter))')
                ->getQuery()
                ->setParameter('fundAwardId', $fundAwardId, UlidType::NAME)
                ->setParameter('year', $sourceYear)
                ->setParameter('quarter', $sourceQuarter)
                ->getResult();

            foreach($sourceSchemeReturns as $sourceSchemeReturn) {
                foreach($this->getMatchingTargetSchemeReturns($targetSchemeReturns, $sourceSchemeReturn) as $targetSchemeReturn) {
                    foreach($sourceSchemeReturn->getMilestones() as $sourceMilestone) {
                        $milestoneType = $sourceMilestone->getType();
                        if (!$milestoneType->isBaselineMilestone()) {
                            continue;
                        }

                        $targetMilestone = $targetSchemeReturn->getMilestoneByType($milestoneType);

                        if ($targetMilestone) {
                            $targetMilestone->setDate($sourceMilestone->getDate());
                        } else {
                            $targetMilestone = (new Milestone())
                                ->setDate($sourceMilestone->getDate())
                                ->setType($milestoneType);

                            $targetSchemeReturn->addMilestone($targetMilestone);
                        }
                    }
                }
            }
        }
    }

    protected function getSchemeReturnBySchemeName(array $schemeReturns, string $schemeName): ?CrstsSchemeReturn
    {
        foreach($schemeReturns as $schemeReturn) {
            if (mb_strtolower($schemeReturn->getScheme()->getName()) === mb_strtolower($schemeName)) {
                return $schemeReturn;
            }
        }

        return null;
    }

    /**
     * @param \Generator<CrstsSchemeReturn> $targetSchemeReturns
     */
    protected function getMatchingTargetSchemeReturns(array $targetSchemeReturns, CrstsSchemeReturn $sourceSchemeReturn): \Generator
    {
        $sourceScheme = $sourceSchemeReturn->getScheme();
        $found = false;

        foreach($targetSchemeReturns as $targetSchemeReturn) {
            $targetScheme = $targetSchemeReturn->getScheme();
            if ($targetScheme === $sourceScheme) {
                yield $targetSchemeReturn;
                $found = true;
            }
        }

        if (!$found) {
            throw new \RuntimeException("No matching schemeReturns for scheme: " . $sourceScheme->getName());
        }
    }
}
