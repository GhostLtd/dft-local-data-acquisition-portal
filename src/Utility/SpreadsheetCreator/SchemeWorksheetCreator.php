<?php

namespace App\Utility\SpreadsheetCreator;

use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Milestone;
use App\Utility\ExpensesTableHelper;
use App\Utility\SpreadsheetCreator\Helper\WorksheetHelper;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
            $milestonesCount = count($milestoneTypes);

            $textColumns = [
                1 => $scheme->getName(),
                2 => $scheme->getDescription(),
                5 => $this->translator->trans("enum.on_track_rating.{$schemeReturn->getOnTrackRating()->value}"),
            ];

            $onTrackColours = match($schemeReturn->getOnTrackRating()->getTagColour()) {
                "red" => [new Color('ff2a0b06'), new Color('fff4cdc6')],
                "green" => [new Color("ff005a30"), new Color('ffcce2d8')],
                "orange" => [new Color('ff6e3619'), new Color('fffcd6c3')],
                "blue" => [new Color('ff00c2d4a'), new Color('ffbbd4ea')],
            };

            $this->helper->at(5, $currentY)
                ->setColor($onTrackColours[0])
                ->setFill($onTrackColours[1]);

            foreach($textColumns as $x => $text) {
                $this->helper->at($x, $currentY)
                    ->setTextWrap(true)
                    ->setVerticalAlignment(Alignment::VERTICAL_TOP)
                    ->setValue($text);

                $this->helper->mergeCells($x, $currentY, $x, $currentY + $milestonesCount - 1);
            }

            foreach($milestoneTypes as $milestoneType) {
                $milestone = $schemeReturn->getMilestoneByType($milestoneType);

                $this->helper->at(3, $currentY)->setValue($this->translator->trans("enum.milestone_type.{$milestoneType->value}"));
                $this->helper->at(4, $currentY)->setValue($milestone?->getDate()?->format('M-y') ?? '-');
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

        $this->helper->at(1, 2)
            ->setValue('Scheme')
            ->setBold(true)
            ->setFill($this->lightGray)
            ->setWidth(50);

        $this->helper->at(2, 2)
            ->setValue('Description')
            ->setBold(true)
            ->setFill($this->lightGray)
            ->setWidth(60);

        $this->helper->at(3, 2)
            ->setValue('Milestone')
            ->setBold(true)
            ->setFill($this->lightGray)
            ->setWidth(20);

        $this->helper->at(4, 2)
            ->setValue('Current forecast / delivered date')
            ->setBold(true)
            ->setFill($this->lightGray)
            ->setWidth(20)
            ->setTextWrap(true);

        $this->helper->at(5, 2)
            ->setValue('On-track rating')
            ->setBold(true)
            ->setFill($this->lightGray)
            ->setWidth(20)
            ->setTextWrap(true);

    }
}
