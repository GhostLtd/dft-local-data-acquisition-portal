<?php

declare(strict_types=1);

namespace App\Command\Import;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

#[AsCommand(name: 'app:import:jess', description: 'Import data from Jess` spreadsheets' )]
class DataImportCommand extends Command
{
    protected const array SHEET_NAMES = [
        'User' => 'User',
        'FundReturn' => 'FundReturn',
        'CrstsFundReturn' => 'CrstsFundReturn',
        'Scheme' => 'Scheme',
        'CrstsSchemeReturn' => 'CrstsSchemeReturn',
        'ExpenseEntry' => 'ExpenseEntry',
        'Milestone' => 'Milestone',
    ];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected PropertyAccessorInterface $propertyAccessor,
        protected LoggerInterface $dataImportLogger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'File to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');
        $spreadsheet = IOFactory::load($file);

        [$year, $quarter] = $this->getYearAndQuarterFromFilename($file);
        $io->title("Importing spreadsheet for {$year} Q{$quarter}");
        $this->dataImportLogger->error("Importing data for {$year} Q{$quarter}...");

        foreach(static::SHEET_NAMES as $classname => $sheetname) {
            $this->dataImportLogger->error("  processing sheet: {$sheetname}");
            $importer = new ("App\\Command\\Import\\{$classname}SheetImporter")(
                $this->entityManager, $this->propertyAccessor, $this->dataImportLogger
            );
            $importer->import($io, $spreadsheet->getSheetByName($sheetname), $year, $quarter);
        }

        return Command::SUCCESS;
    }

    protected function getYearAndQuarterFromFilename(string $filename): array
    {
        if (preg_match('/(?<year>\d{2})\d{2}Q(?<quarter>\d)/', $filename, $matches)) {
            return [intval('20' . $matches['year']), intval($matches['quarter'])];
        }
        throw new \RuntimeException('invalid filename');
    }
}
