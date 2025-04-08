<?php

namespace App\Command\Import;

use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\Enum\BusinessCase;
use App\Entity\SchemeFund\BenefitCostRatio;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class CrstsSchemeReturnSheetImporter extends AbstractSheetImporter
{
    protected const array COLUMNS = [
        'schemeAuthorityName' => 'id',
        'schemeType' => 'scheme_type',
        'progressUpdate' => 'progress_update', // text
        'businessCase' => 'business_case', // enum?
        'expectedBusinessCaseApproval' => 'expected_business_case_approval', // date
        'benefitCostRatio' => 'benefit_cost_ratio_value', // decimal
        'totalCost' => 'total_cost', // decimal
        'agreedFunding' => 'agreed_funding', // decimal
        'spendToDate' => 'spend_to_date', // decimal
    ];

    protected function processRow(Row $row): void
    {
        $values = $this->getCellValues($row);
        unset($values[1]); // type: zebra/retained/standard
        unset($values[8]); // spendToDate??
        [$schemeIdentifier, $values] = $this->extractValueFromArray($values, 0);
        [$schemeName, $authorityName] = array_map(fn($v) => trim($v), explode('_', $schemeIdentifier));

        if (!($scheme = $this->findSchemeByName($schemeName, $authorityName))) {
            $this->io->error("Scheme not found: {$schemeIdentifier}");
            return;
        }
        $return = $this->findCrstsFundReturnByAuthorityName($authorityName);
        $return->addSchemeReturn($schemeReturn = (new CrstsSchemeReturn())->setScheme($scheme));
        $this->persist($schemeReturn);

        $values[3] = $this->attemptToFormatAsEnum(BusinessCase::class, $values[3]);
        if (!$values[4] instanceof \DateTimeInterface) {
            $values[4] = null;
        }
        if ($values[5] !== null) {
            $type = $this->attemptToFormatAsEnum(BenefitCostRatio::class, $values[5]);
            if ($type) {
                $values = (new BenefitCostRatio())->setType($type);
            } else {
                $values[5] = (new BenefitCostRatio())->setValue(floatval($values[5]))->setType(BenefitCostRatioType::VALUE);
            }
        }

        $values[6] = intval($this->attemptToFormatAsDecimal($values[6]) * 1000000);
        $values[7] = intval($this->attemptToFormatAsDecimal($values[7]) * 1000000);

        $this->setColumnValues($schemeReturn, $values);
    }
}