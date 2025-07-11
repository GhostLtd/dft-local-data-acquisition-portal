<?php

namespace App\Utility\SpreadsheetCreator;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Utility\ExpensesTableHelper;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class SchemeWorksheetCreator extends AbstractWorksheetCreator
{
    public function __construct(
        protected ExpensesTableHelper $expensesTableHelper,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    public function addWorksheet(Worksheet $worksheet, CrstsFundReturn $fundReturn): void
    {
        $this->worksheet = $worksheet;

        $this->writeRowHeaders();
        foreach($fundReturn->getSchemeReturns() as $schemeReturn) {

        }
    }

    protected function writeRowHeaders(): void
    {
        $this->worksheet->getCell($this->relXY(1, 1))->setValue('Scheme Details');
    }
}
