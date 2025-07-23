<?php

namespace App\Controller\Admin;

use App\Form\Type\Admin\DataExportType;
use App\Utility\Breadcrumb\Admin\DashboardLinksBuilder;
use App\Utility\CsvExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/data-export')]
class DataExportController extends AbstractController
{
    #[Route(path: '', name: 'admin_data_export')]
    public function dataExport(
        CsvExporter           $csvExporter,
        DashboardLinksBuilder $linksBuilder,
        Request               $request,
    ): Response
    {
        $linksBuilder->setNavLinks(null);
        $form = $this->createForm(DataExportType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $yearAndQuarter = $form->getData()['year_and_quarter'] ?? null;

            $year = null;
            $quarter = null;
            if ($yearAndQuarter) {
                if (preg_match('/^(\d{4})-(\d{1})$/', $yearAndQuarter, $matches)) {
                    $year = intval($matches[1]);
                    $quarter = intval($matches[2]);
                }
            }

            return $csvExporter->exportZip($year, $quarter);
        }

        return $this->render('admin/data_export/index.html.twig', [
            'linksBuilder' => $linksBuilder,
            'form' => $form,
        ]);
    }
}
