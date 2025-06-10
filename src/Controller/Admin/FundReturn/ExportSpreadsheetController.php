<?php

namespace App\Controller\Admin\FundReturn;

use App\Controller\Admin\ForwardRouteTrait;
use App\Utility\Breadcrumb\Admin\DashboardLinksBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExportSpreadsheetController extends AbstractController
{
    use ForwardRouteTrait;

    public function __construct(
        protected DashboardLinksBuilder $linksBuilder,
    ) {}

    #[Route('/fund-return/{fundReturnId}/export-spreadsheet', name: 'admin_fund_return_export_spreadsheet')]
    public function exportSpreadsheet(Request $request): Response
    {
        $this->addRouteAttributes($request);
        return $this->forward("App\Controller\Frontend\FundReturn\ExportSpreadsheetController::exportSpreadsheet", $request->attributes->all(), $request->query->all());
    }

    #[Route('/fund-return/{fundReturnId}/export-spreadsheet/{jobId}', name: 'admin_fund_return_export_spreadsheet_queue')]
    public function queue(Request $request): Response
    {
        $this->addRouteAttributes($request);
        return $this->forward("App\Controller\Frontend\FundReturn\ExportSpreadsheetController::queue", $request->attributes->all(), $request->query->all());
    }

    #[Route('/fund-return/{fundReturnId}/export-spreadsheet/{jobId}/download', name: 'admin_fund_return_export_spreadsheet_download')]
    public function download(Request $request): Response
    {
        $this->addRouteAttributes($request);
        return $this->forward("App\Controller\Frontend\FundReturn\ExportSpreadsheetController::download", $request->attributes->all(), $request->query->all());
    }

    protected function addRouteAttributes(Request $request): void
    {
        $request->attributes->set('linksBuilder', $this->linksBuilder);
        $request->attributes->set('queue_route', 'admin_fund_return_export_spreadsheet_queue');
        $request->attributes->set('download_route', 'admin_fund_return_export_spreadsheet_download');
        $request->attributes->set('redirect_route', 'admin_fund_return');
    }
}
