<?php

declare(strict_types=1);

namespace App\Command\Import;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
    private const SHEET_NAMES = [
        'User',
        'FundReturn',
        'CrstsFundReturn',
        'Scheme',
        'CrstsSchemeReturn',
        'ExpenseEntry',
        'Milestone',
    ];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected PropertyAccessorInterface $propertyAccessor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
//            ->addOption('sheet', 's', InputOption::VALUE_REQUIRED, 'sheet name to import', 'ExpenseEntry')
            ->addArgument('file', InputArgument::REQUIRED, 'File to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');
        $spreadsheet = IOFactory::load($file);

        [$year, $quarter] = $this->getYearAndQuarterFromFilename($file);
        $io->title("Importing spreadsheet for {$year} Q{$quarter}");

        foreach(self::SHEET_NAMES as $name) {
            $importer = new ("App\\Command\\Import\\{$name}SheetImporter")($this->entityManager, $this->propertyAccessor);
            $importer->import($io, $spreadsheet->getSheetByName($name), $year, $quarter);
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
