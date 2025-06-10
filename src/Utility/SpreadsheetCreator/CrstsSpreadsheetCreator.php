<?php

namespace App\Utility\SpreadsheetCreator;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

class CrstsSpreadsheetCreator
{
    public function __construct(
        protected FundDetailsWorksheetCreator  $fundDetailsWorksheetCreator,
        protected FundExpensesWorksheetCreator $fundExpensesWorksheetCreator,
        protected SchemeWorksheetCreator       $schemeWorksheetCreator,
    ) {}

    public function getSpreadsheetForFundReturn(CrstsFundReturn $fundReturn): Xlsx
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $this->fundDetailsWorksheetCreator->addWorksheet($sheet, $fundReturn);

        $sheet = $spreadsheet->createSheet();
        $this->fundExpensesWorksheetCreator->addWorksheet($sheet, $fundReturn);

        $sheet = $spreadsheet->createSheet();
        $this->schemeWorksheetCreator->addWorksheet($sheet, $fundReturn);

        $spreadsheet->setActiveSheetIndex(0);

        return new Xlsx($spreadsheet);
    }
}
