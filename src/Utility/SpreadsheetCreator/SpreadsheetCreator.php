<?php

namespace App\Utility\SpreadsheetCreator;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;

class SpreadsheetCreator
{
    public function __construct(
        protected FundWorksheetCreator   $fundWorksheetCreator,
        protected SchemeWorksheetCreator $schemeWorksheetCreator,
    ) {}

    public function getSpreadsheetForFundReturn(FundReturn $fundReturn): Xlsx
    {
        if (!$fundReturn instanceof CrstsFundReturn) {
            throw new AccessDeniedException('Only CRSTS returns are currently supported');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $this->fundWorksheetCreator->addWorksheet($sheet, $fundReturn);

        $sheet = $spreadsheet->createSheet();
        $this->schemeWorksheetCreator->addWorksheet($sheet, $fundReturn);

        return new Xlsx($spreadsheet);
    }
}