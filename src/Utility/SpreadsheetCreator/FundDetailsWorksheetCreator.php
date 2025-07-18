<?php

namespace App\Utility\SpreadsheetCreator;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Utility\SpreadsheetCreator\WorksheetHelper\StyleActionSet;
use App\Utility\SpreadsheetCreator\WorksheetHelper\WorksheetHelper;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class FundDetailsWorksheetCreator extends AbstractWorksheetCreator
{
    protected WorksheetHelper $helper;

    public function __construct(
        protected TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    public function addWorksheet(Worksheet $worksheet, CrstsFundReturn $fundReturn): void
    {
        $this->worksheet = $worksheet;
        $this->worksheet->setTitle('Fund details');
        $this->helper = new WorksheetHelper($this->worksheet);

        $headerStyle = (new StyleActionSet())
            ->setColor($this->white)
            ->setFill($this->blue)
            ->setBold(true)
            ->setAllBorders($this->black);

        $textAreaStyle = (new StyleActionSet())
            ->setWrapText(true)
            ->setAllBorders($this->black)
            ->setVerticalAlignment(Alignment::VERTICAL_TOP);

        $this->helper->cell(1, 2)->freezePane();

        $this->helper->cell(2, 1)->setWidth(22);
        $this->helper->cell(3, 1)->setWidth(75);
        $this->helper->cell(4, 1)->setWidth(20);

        $this->helper->cell(6, 1)->setWidth(22);
        $this->helper->cell(7, 1)->setWidth(75);
        $this->helper->cell(8, 1)->setWidth(20);

        $this->helper->range(1, 1, 9, 1)
            ->mergeCells()
            ->setValue('Fund details')
            ->apply($headerStyle);

        $this->helper->cell(2, 3)
            ->setValue('Reporting period')
            ->apply($headerStyle);

        $this->helper->cell(3, 3)
            ->setValue($fundReturn->getYear().'-'.$fundReturn->getNextYearAsTwoDigits().' Q'.$fundReturn->getQuarter())
            ->setAllBorders($this->black);

        $signoffYAdjustment = 0;
        if ($fundReturn->isSignedOff()) {
            $this->helper->range(6, 3, 7, 3)
                ->mergeCells()
                ->setValue('Sign-off')
                ->apply($headerStyle);

            $this->helper->cell(6, 4)
                ->setValue('Name');

            $this->helper->cell(7, 4)
                ->setValue($fundReturn->getSignoffName());

            $this->helper->cell(6, 5)
                ->setValue('Email');

            $this->helper->cell(7, 5)
                ->setValue($fundReturn->getSignoffEmail());

            $this->helper->cell(6, 6)
                ->setValue('Date');

            $this->helper->cell(7, 6)
                ->setValue(Date::PHPToExcel($fundReturn->getSignoffDate()))
                ->setNumberFormatCode('dd/mm/yyyy')
                ->setHorizontalAlignment(Alignment::HORIZONTAL_LEFT);

            $this->helper->range(6, 4, 6, 6)
                ->setFill($this->lightBlue)
                ->setBold(true)
                ->setAllBorders($this->black);

            $this->helper->range(7, 4, 7, 6)
                ->setAllBorders($this->black);

            $signoffYAdjustment = 3;
        }

        // Textarea-style values
        $textAreas = [
            [
                'x' => 2,
                'y' => 5 + $signoffYAdjustment,
                'title' => $this->translator->trans('frontend.pages.fund_return.overall_progress_summary'),
                'value' => $fundReturn->getProgressSummary(),
            ],
            [
                'x' => 2,
                'y' => 8 + $signoffYAdjustment,
                'title' => $this->translator->trans('frontend.pages.fund_return.local_contribution'),
                'value' => $fundReturn->getLocalContribution(),
            ],
            [
                'x' => 6,
                'y' => 8 + $signoffYAdjustment,
                'title' => $this->translator->trans('frontend.pages.fund_return.resource_funding'),
                'value' => $fundReturn->getResourceFunding(),
            ],
            [
                'x' => 2,
                'y' => 11 + $signoffYAdjustment,
                'title' => $this->translator->trans('frontend.pages.fund_return.comments'),
                'value' => $fundReturn->getComments(),
            ],
        ];

        foreach($textAreas as ['title' => $title, 'value' => $value, 'x' => $x, 'y' => $y]) {
            $this->helper->range($x, $y, $x + 2, $y)
                ->apply($headerStyle)
                ->mergeCells()
                ->setValue($title);

            $this->helper->range($x, $y + 1, $x + 2, $y + 1)
                ->apply($textAreaStyle)
                ->mergeCells()
                ->setValue($value)
                ->setHeight(240);
        }

        // Delivery confidence
        (function(int $x, int $y) use ($fundReturn, $headerStyle, $textAreaStyle) {
            $this->helper->range($x, $y, $x + 2, $y)
                ->mergeCells()
                ->setValue($this->translator->trans('frontend.pages.fund_return.overall_delivery_confidence'))
                ->apply($headerStyle);

            $this->helper->range($x, $y + 1, $x + 1, $y + 1)
                ->mergeCells()
                ->apply($textAreaStyle)
                ->setValue($fundReturn->getDeliveryConfidence())
                ->setHeight(180);

            $tagColour = $fundReturn->getOverallConfidence()?->getTagColour();
            $cell = $this->helper->cell($x + 2, $y + 1);

            if ($tagColour) {
                $colour = $this->getTagColours($tagColour);
                $cell
                    ->setValue($this->translator->trans("enum.rating.{$fundReturn->getOverallConfidence()->value}"))
                    ->setColor($colour[0])
                    ->setFill($colour[1])
                    ->setVerticalAlignment(Alignment::VERTICAL_CENTER)
                    ->setHorizontalAlignment(Alignment::HORIZONTAL_CENTER);
            }

            $cell->setAllBorders($this->black);
        })(6, 5 + $signoffYAdjustment);
    }
}
