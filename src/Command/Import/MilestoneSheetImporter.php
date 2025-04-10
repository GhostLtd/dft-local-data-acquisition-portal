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
        'type' => 'attribute',
        'date' => 'value',
        'flag' => 'baseline_flag',
        'identifier' => 'name_location',
    ];

    protected function processRow(Row $row): void
    {
        $values = $this->getCellValues($row);
        unset($values['flag']);
        $schemeIdentifier = $this->extractValueFromArray($values, 'identifier');
        if ($this->isMissingZebraScheme($schemeIdentifier)) {return;}

        [$schemeName, $authorityName] = $this->getSchemeAndAuthorityNames($schemeIdentifier);
        if (!($schemeReturn = $this->findCrstsSchemeReturnByName($schemeName, $authorityName))) {
            $this->logger->error("Scheme return not found", [$authorityName, $schemeName]);
            return;
        }

        $values['type'] = $this->attemptToFormatAsMilestoneType($values['type']);
        if (!$values['type']) {
            $this->logger->error("Invalid MilestoneType", [$authorityName, $schemeName, $values['type']]);
            return;
        }
        $values['date'] = $this->attemptToFormatAsDate($values['date']);

        $milestone = $schemeReturn->getMilestones()?->findFirst(fn($k, Milestone $m) => $values['type'] === $m->getType())
            ?? (new Milestone());
        $schemeReturn->addMilestone($milestone);

        $this->setColumnValues($milestone, $values);

        $this->persist($milestone);
    }

    protected function attemptToFormatAsMilestoneType(?string $value): ?MilestoneType
    {
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