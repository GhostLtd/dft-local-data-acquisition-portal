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
        protected FundWorksheetCreator $fundWorksheetCreator,
    ) {}

    public function getSpreadsheetForFundReturn(FundReturn $fundReturn): Xlsx {
        if (!$fundReturn instanceof CrstsFundReturn) {
            throw new AccessDeniedException('Only CRSTS returns are currently supported');
        }

        $spreadsheet = new Spreadsheet();
        $this->fundWorksheetCreator->addFundWorksheet($spreadsheet->getActiveSheet(), $fundReturn);

        return new Xlsx($spreadsheet);
    }
}