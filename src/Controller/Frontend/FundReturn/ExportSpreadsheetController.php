<?php

namespace App\Controller\Frontend\FundReturn;

use App\Config\ExpenseDivision\ColumnConfiguration;
use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\TotalConfiguration;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Repository\SchemeRepository;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use App\Utility\SpreadsheetCreator\SpreadsheetCreator;
use Brick\Math\BigDecimal;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportSpreadsheetController extends AbstractController
{
    public function __construct(
        protected SchemeRepository    $schemeRepository,
        protected TranslatorInterface $translator,
    ) {}

    #[Route('/fund-return/{fundReturnId}/export-spreadsheet', name: 'app_fund_return_export_spreadsheet')]
    #[IsGranted(Role::CAN_EXPORT_SPREADSHEET, 'fundReturn')]
    public function exportSpreadsheet(
        #[MapEntity(expr: 'repository.findForSpreadsheetExport(fundReturnId)')]
        FundReturn          $fundReturn,
        SpreadsheetCreator  $spreadsheetCreator,
        SluggerInterface    $slugger,
    ): Response
    {
        if (!$fundReturn instanceof CrstsFundReturn) {
            throw new AccessDeniedException('Only CRSTS returns are currently supported');
        }

        $xlsx = $spreadsheetCreator->getSpreadsheetForFundReturn($fundReturn);

        $authorityName = $slugger->slug($fundReturn->getFundAward()->getAuthority()->getName())->lower();
        $datePeriod = "{$fundReturn->getYear()}-{$fundReturn->getNextYearAsTwoDigits()}-Q{$fundReturn->getQuarter()}";
        $now = (new \DateTime())->format('Ymd_Hisv');
        $filename = "{$datePeriod}_{$authorityName}_CRSTS_{$now}.xls";

        return new StreamedResponse(function () use ($xlsx) {
            $xlsx->save('php://output');
        }, 200, [
            'content-type' => 'application/vnd.ms-excel',
            'content-disposition' => "attachment; filename={$filename}",
        ]);
    }

}
