<?php

declare(strict_types=1);

namespace App\Command\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import:jess', description: 'Import data from Jess` spreadsheets' )]
class DataImportCommand extends Command
{
    private const SHEET_NAMES = [
        'CrstsFundReturn',
        'CrstsSchemeReturn',
        'Scheme',
        'ExpenseEntry',
        'User',
        'FundReturn',
        'Milestone',
    ];

    public function __construct(
        protected UserSheetImporter $userImporter,
        protected FundReturnSheetImporter $fundReturnImporter,
        protected CrstsFundReturnSheetImporter $crstsFundReturnImporter,
        protected SchemeSheetImporter $schemeImporter,
        protected CrstsSchemeReturnSheetImporter $crstsSchemeReturnImporter,
//        protected ExpenseEntrySheetImporter $expenseEntryImporter,
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

        $this->userImporter->import($io, $spreadsheet->getSheetByName('User'), $year, $quarter);
        $this->fundReturnImporter->import($io, $spreadsheet->getSheetByName('FundReturn'), $year, $quarter);
        $this->crstsFundReturnImporter->import($io, $spreadsheet->getSheetByName('CrstsFundReturn'), $year, $quarter);
        $this->schemeImporter->import($io, $spreadsheet->getSheetByName('Scheme'), $year, $quarter);
        $this->crstsSchemeReturnImporter->import($io, $spreadsheet->getSheetByName('CrstsSchemeReturn'), $year, $quarter);
//        $this->expenseEntryImporter->import($io, $spreadsheet->getSheetByName('ExpenseEntry'), $year, $quarter);

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
