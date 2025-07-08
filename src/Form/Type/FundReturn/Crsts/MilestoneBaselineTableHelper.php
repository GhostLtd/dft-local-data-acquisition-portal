<?php

namespace App\Form\Type\FundReturn\Crsts;

use App\Config\Table\Cell;
use App\Config\Table\Header;
use App\Config\Table\Row;
use App\Config\Table\Table;
use App\Config\Table\TableBody;
use App\Config\Table\TableHead;
use App\Entity\Enum\MilestoneType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use Symfony\Component\Translation\TranslatableMessage;

class MilestoneBaselineTableHelper
{
    protected CrstsFundReturn $fundReturn;

    public function setFundReturn(CrstsFundReturn $fundReturn): static {
        $this->fundReturn = $fundReturn;
        return $this;
    }

    public function getTable(): Table
    {
        $headers = [
            new Header([]),
        ];

        /** @var MilestoneType[] $baselineTypes */
        $baselineTypes = [
            MilestoneType::BASELINE_START_DEVELOPMENT,
            MilestoneType::BASELINE_END_DEVELOPMENT,
            MilestoneType::BASELINE_START_CONSTRUCTION,
            MilestoneType::BASELINE_END_CONSTRUCTION,
            MilestoneType::BASELINE_FINAL_DELIVERY,
        ];
        foreach($baselineTypes as $type) {
            $counterpart = $type->getNonBaselineCounterpart();
            $headers[] = new Header([
                'text' => new TranslatableMessage("enum.milestone_type.{$counterpart->value}"),
            ]);
        }

        $headers[] = new Header([]);

        $bodyRows = [];

        $schemeReturns = $this->fundReturn->getSchemeReturns()->toArray();
        usort($schemeReturns, fn(SchemeReturn $a, SchemeReturn $b) => $a->getScheme()->getName() <=> $b->getScheme()->getName());

        foreach($schemeReturns as $schemeReturn) {
            $schemeReturnId = $schemeReturn->getId()->toRfc4122();
            $cells = [
                new Header([
                    'text' => $schemeReturn->getScheme()->getName(),
                ]),
            ];

            foreach($baselineTypes as $type) {
                $milestone = $schemeReturn->getMilestoneByType($type);
                $date = $milestone ? $milestone->getDate()->format('d/m/Y') : '-';

                $cells[] = new Cell([
                    'key' => "milestone__{$schemeReturnId}__{$type->value}",
                    'text' => $date,
                ]);
            }

            $cells[] = new Cell([
                'key' => "milestone__{$schemeReturnId}__links",
                'html' => '<a class="govuk-link" href="#">edit</a>',
            ]);

            $bodyRows[] = new Row($cells, ['classes' => 'ungrouped']);
        }

        return new Table([
            new TableHead([new Row($headers)]),
            new TableBody($bodyRows),
        ], [
            'classes' => 'milestone-baselines',
        ]);
    }
}
