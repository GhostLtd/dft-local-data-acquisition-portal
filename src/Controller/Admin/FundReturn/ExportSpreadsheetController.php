<?php

namespace App\Controller\Admin\FundReturn;

use App\Controller\Admin\ForwardRouteTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExportSpreadsheetController extends AbstractController
{
    use ForwardRouteTrait;

    #[Route('/fund-return/{fundReturnId}/export-spreadsheet', name: 'admin_fund_return_export_spreadsheet')]
    public function exportSpreadsheet(
        Request $request,
    ): Response
    {
        return $this->forward("App\Controller\Frontend\FundReturn\ExportSpreadsheetController::exportSpreadsheet", $request->attributes->all(), $request->query->all());
    }
}
