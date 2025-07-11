<?php

namespace App\Utility\SpreadsheetCreator;

use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Utility\ExpensesTableHelper;
use App\Utility\SpreadsheetCreator\WorksheetHelper\ActionSet;
use App\Utility\SpreadsheetCreator\WorksheetHelper\WorksheetHelper;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class SchemeWorksheetCreator extends AbstractWorksheetCreator
{
    protected WorksheetHelper $helper;

    public function __construct(
        protected ExpensesTableHelper $expensesTableHelper,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    public function addWorksheet(Worksheet $worksheet, CrstsFundReturn $fundReturn): void
    {
        $this->worksheet = $worksheet->setTitle('Scheme milestones');
        $this->helper = new WorksheetHelper($this->worksheet);

        $this->writeRowHeaders();

        $currentY = 3;
        foreach($fundReturn->getSchemeReturns() as $schemeReturn) {
            $scheme = $schemeReturn->getScheme();

            $isCDEL = $scheme->getCrstsData()->getFundedMostlyAs() === FundedMostlyAs::CDEL;
            $milestoneTypes = MilestoneType::getNonBaselineCases($isCDEL);

            if ($schemeReturn->getDevelopmentOnly()) {
                $milestoneTypes = array_filter($milestoneTypes, fn(MilestoneType $t) => $t->isDevelopmentMilestone());
            }

            $milestonesCount = count($milestoneTypes);

            // General text column options
            $transportMode = $scheme->getTransportMode()?->getForDisplay();
            $activeTravelElement = $scheme->getActiveTravelElement()?->getForDisplay();
            $businessCase = $schemeReturn->getBusinessCase()?->value;

            $textColumns = [
                1 => $scheme->getSchemeIdentifier(),
                2 => $scheme->getName(),
                3 => $scheme->getDescription(),
                4 => $this->translator->trans("enum.on_track_rating.{$schemeReturn->getOnTrackRating()->value}"),
                5 => match($schemeReturn->getDevelopmentOnly()) {
                    true => 'Y',
                    false => 'N',
                    null => '-',
                },
                8 => $schemeReturn->getProgressUpdate(),
                9 => match($scheme->getCrstsData()->isPreviouslyTcf()) {
                    true => 'Y',
                    false => 'N',
                    null => '-',
                },
                10 => $schemeReturn->getRisks(),
                11 => $transportMode ? $transportMode->trans($this->translator) : '-',
                12 => $activeTravelElement ? $activeTravelElement->trans($this->translator) : '-',
                13 => $businessCase ? $this->translator->trans("enum.business_case.{$businessCase}") : '-',
                14 => $schemeReturn->getExpectedBusinessCaseApproval()?->format('M-y') ?? '-',
                15 => match($schemeReturn->getBenefitCostRatio()?->getType()) {
                    BenefitCostRatioType::NA => 'N/A',
                    BenefitCostRatioType::TBC => 'TBC',
                    BenefitCostRatioType::VALUE => $schemeReturn->getBenefitCostRatio()->getValue(),
                    null => '-',
                },
            ];

            foreach($textColumns as $x => $text) {
                $this->helper->at($x, $currentY)
                    ->setTextWrap(true)
                    ->setVerticalAlignment(Alignment::VERTICAL_TOP)
                    ->setValue($text);

                $this->helper->mergeCells($x, $currentY, $x, $currentY + $milestonesCount - 1);
            }

            // On-track rating
            $onTrackColours = match($schemeReturn->getOnTrackRating()->getTagColour()) {
                "red" => [new Color('ff2a0b06'), new Color('fff4cdc6')],
                "green" => [new Color("ff005a30"), new Color('ffcce2d8')],
                "orange" => [new Color('ff6e3619'), new Color('fffcd6c3')],
                "blue" => [new Color('ff00c2d4a'), new Color('ffbbd4ea')],
            };

            $this->helper->at(4, $currentY)
                ->setColor($onTrackColours[0])
                ->setFill($onTrackColours[1])
                ->setVerticalAlignment(Alignment::VERTICAL_CENTER)
                ->setHorizontalAlignment(Alignment::HORIZONTAL_CENTER);

            // BCR
            $this->helper->at(15, $currentY)
                ->setHorizontalAlignment(Alignment::HORIZONTAL_LEFT);

            // Milestone types
            foreach($milestoneTypes as $milestoneType) {
                $milestone = $schemeReturn->getMilestoneByType($milestoneType);

                $this->helper->at(6, $currentY)->setValue($this->translator->trans("enum.milestone_type.{$milestoneType->value}"));
                $this->helper->at(7, $currentY)->setValue($milestone?->getDate()?->format('M-y') ?? '-');
                $currentY++;
            }
        }
    }

    protected function writeRowHeaders(): void
    {
        $this->helper->at(1, 1)
            ->setValue('Scheme Details')
            ->setBold(true)
            ->setColor($this->white)
            ->setFill($this->blue);

        $styles = (new ActionSet())
            ->setBold(true)
            ->setFill($this->lightGray)
            ->setWidth(20);

        $this->helper->at(1, 2)
            ->setValue('Identifier')
            ->apply($styles);

        $this->helper->at(2, 2)
            ->setValue('Scheme')
            ->apply($styles)
            ->setWidth(50);

        $this->helper->at(3, 2)
            ->setValue('Description')
            ->apply($styles)
            ->setWidth(60);

        $this->helper->at(4, 2)
            ->setValue('On-track rating')
            ->apply($styles)
            ->setTextWrap(true);

        $this->helper->at(5, 2)
            ->setValue('Development only?')
            ->apply($styles);

        $this->helper->at(6, 2)
            ->setValue('Milestone')
            ->apply($styles);

        $this->helper->at(7, 2)
            ->setValue('Current forecast / delivered date')
            ->apply($styles)
            ->setTextWrap(true);

        $this->helper->at(8, 2)
            ->setValue('Progress update')
            ->apply($styles)
            ->setWidth(60);

        $this->helper->at(9, 2)
            ->setValue('Transforming Cities Fund?')
            ->apply($styles)
            ->setTextWrap(true);

        $this->helper->at(10, 2)
            ->apply($styles)
            ->setWidth(60);

        $this->helper->at(11, 2)
            ->setValue('Transport mode')
            ->apply($styles)
            ->setWidth(35);

        $this->helper->at(12, 2)
            ->setValue('Active travel element')
            ->apply($styles)
            ->setWidth(30);

        $this->helper->at(13, 2)
            ->setValue('Current business case')
            ->apply($styles)
            ->setWidth(25);

        $this->helper->at(14, 2)
            ->setValue('Expected date of next approval gateway')
            ->apply($styles)
            ->setTextWrap(true);

        $this->helper->at(15, 2)
            ->setValue('BCR')
            ->apply($styles);
    }
}
