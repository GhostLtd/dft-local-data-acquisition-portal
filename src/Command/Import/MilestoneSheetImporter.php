<?php

namespace App\Command\Import;

use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\MilestoneType;
use App\Entity\Milestone;
use App\Entity\PermissionsView;
use App\Entity\SchemeFund\BenefitCostRatio;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class MilestoneSheetImporter extends AbstractSheetImporter
{
    protected const array COLUMNS = [
        'date' => 'value',
        'flag' => 'baseline_flag',
        'type' => 'milestone_type',
        'identifier' => 'name_location',
    ];

    protected function processRow(Row $row): void
    {
        $originalValues = $values = $this->getCellValues($row);
        unset($values['flag']);
        $schemeIdentifier = $this->extractValueFromArray($values, 'identifier');

        [$schemeName, $authorityName] = $this->getSchemeAndAuthorityNames($schemeIdentifier);
        if (!($schemeReturn = $this->findCrstsSchemeReturnByName($schemeName, $authorityName))) {
            $this->logger->error("Scheme return not found", [$authorityName, $schemeName]);
            return;
        }

        $values['type'] = $this->attemptToFormatAsMilestoneType($values['type']);
        if (!$values['type']) {
            $this->logger->error("Invalid MilestoneType", [$values['type'], $authorityName, $schemeName, $originalValues]);
            return;
        }
        $values['date'] = $this->attemptToFormatAsDate($values['date']);

        $milestone = $schemeReturn->getMilestones()?->findFirst(fn($k, Milestone $m) => $values['type'] === $m->getType())
            ?? (new Milestone());
        $schemeReturn->addMilestone($milestone);

        $this->setColumnValues($milestone, $values);

        $this->persist($milestone);

        // as per call with Bella/Jess, we want to add final delivery milestone as copy of end construction.
        // we will be collecting this as a separate value moving forwards
        if ($values['type'] === MilestoneType::END_CONSTRUCTION) {
            $fdMilestone = (new Milestone())
                ->setType(MilestoneType::FINAL_DELIVERY)
                ->setDate($values['date'])
            ;
            $schemeReturn->addMilestone($fdMilestone);
            $this->persist($fdMilestone);
        }
    }

    protected function attemptToFormatAsMilestoneType(?string $value): ?MilestoneType
    {
        $ignoredZebMilestones = [
            'First ZEBs ordered',
            'First ZEBS delivered',
            'Final ZEBs enter service',
            'First Charging/refuelling infrastructure ordered',
            'Final Charging/refuelling infrastructure installed',
            'First Grid connection work ordered',
            'Final Grid connection work complete',
            'ZEBs ordered (CCS)',
            'ZEBS delivered (CCS)',
            'ZEBs enter service (CCS)',
            'Charging/refuelling infrastructure ordered (CCS)',
            'Charging/refuelling infrastructure installed (CCS)',
            '',
        ];
        $value = strtoupper(str_replace([' / delivery', ' '], ['', '_'], $value));
        /** @var MilestoneType $enum */
        try {
            $enum = (new \ReflectionEnumBackedCase(MilestoneType::class, $value))->getValue();
        } catch (\Throwable $e) {
            return null;
        }
        return $enum;
    }
}