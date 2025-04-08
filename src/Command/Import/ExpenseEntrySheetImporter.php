<?php

namespace App\Command\Import;

use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExpenseEntrySheetImporter extends AbstractSheetImporter
{
    protected const array COLUMNS = [
        'type',
        'division',
        'subDivision',
        'value',
        'name_location',
        'forecast',
    ];
    public function import(SymfonyStyle $io, Worksheet $sheet, int $year, int $quarter): void
    {
        parent::import($io, $sheet, $year, $quarter);
        dump($this->getCellValues($sheet->getRowIterator(2)->current()));
    }

    protected function processRow(Row $row): void
    {
        // TODO: Implement processRow() method.
    }
}