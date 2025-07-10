<?php

namespace App\Form\Type\FundReturn\Crsts;

use App\Config\Table\Cell;
use App\Config\Table\Header;
use App\Config\Table\Row;
use App\Config\Table\Table;
use App\Config\Table\TableBody;
use App\Config\Table\TableHead;
use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;

class MilestoneBaselineTableHelper
{
    protected CrstsFundReturn $fundReturn;

    public function __construct(protected UrlGeneratorInterface $urlGenerator)
    {}

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
            $mostlyFundedAs = $schemeReturn->getScheme()->getCrstsData()->getFundedMostlyAs();

            $cells = [
                new Header([
                    'text' => $schemeReturn->getScheme()->getName(),
                ]),
            ];

            foreach($baselineTypes as $type) {
                if ($mostlyFundedAs === FundedMostlyAs::RDEL) {
                    $type = match($type) {
                        MilestoneType::BASELINE_START_CONSTRUCTION => MilestoneType::BASELINE_START_DELIVERY,
                        MilestoneType::BASELINE_END_CONSTRUCTION => MilestoneType::BASELINE_END_DELIVERY,
                        default => $type,
                    };
                }

                $milestone = $schemeReturn->getMilestoneByType($type);
                $date = $milestone?->getDate();

                $cells[] = new Cell([
                    'key' => "milestone__{$schemeReturnId}__{$type->value}",
                    'text' => $date ? $date->format('d/m/Y') : '-',
                ]);
            }

            $link = htmlspecialchars(
                $this->urlGenerator->generate('admin_scheme_return_milestone_baselines_edit', [
                    'schemeId' => $schemeReturn->getScheme()->getId(),
                    'fundReturnId' => $schemeReturn->getFundReturn()->getId(),
                ]), \ENT_QUOTES | \ENT_SUBSTITUTE
            );

            $cells[] = new Cell([
                'key' => "milestone__{$schemeReturnId}__links",
                'html' => '<a class="govuk-link" href="'.$link.'">edit</a>',
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
