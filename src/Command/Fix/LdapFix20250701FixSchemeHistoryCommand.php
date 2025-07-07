<?php

namespace App\Command\Fix;

use App\Entity\Enum\Fund;
use App\Entity\PropertyChangeLog;
use App\Entity\Scheme;
use App\Repository\PropertyChangeLogRepository;
use App\Repository\SchemeRepository;
use App\Utility\FinancialQuarter;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ldap:fix:20250701-fix-scheme-history',
    description: 'Adds missing scheme history into the property change log, assuming no changes',
)]
class LdapFix20250701FixSchemeHistoryCommand extends AbstractSheetBasedCommand
{
    protected array $currentDataValues = [];

    public function __construct(
        protected EntityManagerInterface      $entityManager,
        protected PropertyChangeLogRepository $propertyChangeLogRepository,
        protected SchemeRepository            $schemeRepository
    )
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

        $io->writeln('<info>Setting description history</info>');
        // Set description history from source files
        $sourcesByYear = $this->getSourcesByYear($io, $importPath);
        foreach(array_keys($sourcesByYear) as $year) {
            foreach($sourcesByYear[$year] as $quarter => $path) {
                $this->import($io, $year, $quarter, $path);
            }
        }

        // Set initial values for fields other than description
        $io->writeln('<info>Setting initial history for other values</info>');
        $schemes = $this->schemeRepository->findAll();

        $earliestYear = 2023;
        $earliestQuarter = 4;
        $io->writeln("- Q{$earliestQuarter} {$earliestYear}");

        $financialQuarter = new FinancialQuarter($earliestYear, $earliestQuarter);
        $timestamp = $financialQuarter->getNextQuarter()->getStartDate();
        $add = function (Scheme $scheme, string $propertyName, mixed $propertyValue) use ($timestamp) {
            $pcl = (new PropertyChangeLog())
                ->setEntityClass(Scheme::class)
                ->setEntityId($scheme->getId())
                ->setAction('insert')
                ->setPropertyName($propertyName)
                ->setPropertyValue($propertyValue)
                ->setSource('ldap:fix:20250701-fix-scheme-history')
                ->setTimestamp($timestamp);

            $this->debug($pcl);
            $this->entityManager->persist($pcl);
        };

        foreach($schemes as $scheme) {
            if ($this->propertyChangeLogRepository->findOneBy(['entityId' => $scheme->getId(), 'action' => 'insert'])) {
                $io->writeln("<comment>Skipping:</comment> Insert already exists for {$scheme->getName()}");
                continue;
            }

            $add($scheme, 'activeTravelElement', $scheme->getActiveTravelElement()?->value);
            $add($scheme, 'crstsData.fundedMostlyAs', $scheme->getCrstsData()->getFundedMostlyAs());
            $add($scheme, 'crstsData.previouslyTcf', $scheme->getCrstsData()->isPreviouslyTcf());
            $add($scheme, 'crstsData.retained', $scheme->getCrstsData()->isRetained());
            $add($scheme, 'funds', join(',', array_map(fn(Fund $f) => $f->name, $scheme->getFunds())));
            $add($scheme, 'name', $scheme->getName());
            $add($scheme, 'transportMode', $scheme->getTransportMode()?->value);
            $add($scheme, 'schemeIdentifier', $scheme->getSchemeIdentifier());
        }

        $this->entityManager->flush();
        return Command::SUCCESS;
    }


    protected function import(SymfonyStyle $io, string $year, string $quarter, string $importPath): void
    {
        $reader = IOFactory::createReaderForFile($importPath)
            ->setLoadSheetsOnly(['Scheme'])
            ->setReadDataOnly(true);

        $spreadsheet = $reader->load($importPath);
        $sheet = $spreadsheet->getSheetByName('Scheme');

        $io->writeln("- Q{$quarter} {$year}");

        $nnTrim = fn(?string $v) => $v === null ? null : trim($v);

        $financialQuarter = new FinancialQuarter($year, $quarter);
        $timestamp = $financialQuarter->getNextQuarter()->getStartDate();

        $isEarliest = $financialQuarter->initialYear === 2023 && $financialQuarter->quarter === 4;


        foreach($sheet->getRowIterator(2) as $row) {
            $rowIndex = $row->getRowIndex();

            $name = $nnTrim($sheet->getCell([1, $rowIndex])->getValue());
            $authName = $nnTrim($sheet->getCell([2, $rowIndex])->getValue());
//            $isRetained = $sheet->getCell([3, $rowIndex])->getValue();
            $description = $nnTrim($sheet->getCell([4, $rowIndex])->getValue());
//            $wasTcf = $sheet->getCell([5, $rowIndex])->getValue();
//            $transportMode = $nnTrim($sheet->getCell([6, $rowIndex])->getValue());
            $schemeType = $nnTrim($sheet->getCell([7, $rowIndex])->getValue());

            if ($schemeType !== 'CRSTS1') {
                continue;
            }

            $authName = $this->convertAuthorityName($authName);

            $scheme = $this->schemeRepository->createQueryBuilder('s')
                ->join('s.authority', 'a')
                ->where('s.name = :scheme_name')
                ->andWhere('a.name = :authority_name')
                ->setParameter('scheme_name', $name)
                ->setParameter('authority_name', $authName)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$scheme) {
                $io->writeln("<error>No match for '{$name}' ($authName)</error>");
                continue;
            }

            if ($this->propertyChangeLogRepository->findOneBy(['entityId' => $scheme->getId(), 'action' => 'insert'])) {
                $io->writeln("<comment>Skipping:</comment> Insert already exists for {$scheme->getName()}");
                continue;
            }

            $id = $scheme->getId()->toRfc4122();
            $this->currentDataValues[$id] ??= [];

            // Description seems to be the only reliable data point in the import files
            $current = $this->currentDataValues[$id]['description'] ?? null;

            if ($description !== $current) {
                $pcl = (new PropertyChangeLog())
                    ->setEntityClass(Scheme::class)
                    ->setEntityId($scheme->getId())
                    ->setAction($isEarliest ? 'insert' : 'update')
                    ->setPropertyName('description')
                    ->setPropertyValue($description)
                    ->setSource('ldap:fix:20250701-fix-scheme-history')
                    ->setTimestamp($timestamp);

                $this->debug($pcl);
                $this->entityManager->persist($pcl);

                $this->currentDataValues[$id]['description'] = $description;
            }
        }
    }

    protected function debug(PropertyChangeLog $pcl): void
    {

    }
}
