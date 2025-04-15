<?php

namespace App\Command\Import;

use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\Enum\BusinessCase;
use App\Entity\PermissionsView;
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
        unset($values['schemeType']); // type: zebra/retained/standard
        unset($values['spendToDate']);
        $schemeIdentifier = $this->extractValueFromArray($values, 'schemeAuthorityName');

        [$schemeName, $authorityName] = $this->getSchemeAndAuthorityNames($schemeIdentifier);
        if (!($scheme = $this->findSchemeByName($schemeName, $authorityName))) {
            $this->logger->error("Scheme not found", [$schemeIdentifier]);
            return;
        }
        $return = $this->findCrstsFundReturnByAuthorityName($authorityName);
        $return->addSchemeReturn($schemeReturn = (new CrstsSchemeReturn())->setScheme($scheme));

        $values['businessCase'] = $this->attemptToFormatAsBusinessCase($values['businessCase']);
        $values['expectedBusinessCaseApproval'] = $this->attemptToFormatAsDate($values['expectedBusinessCaseApproval']);
        $values['benefitCostRatio'] = $this->attemptToFormatAsBcr($values['benefitCostRatio']);
        $values['totalCost'] = $this->attemptToFormatAsFinancial($values['totalCost']);
        $values['agreedFunding'] = $this->attemptToFormatAsFinancial($values['agreedFunding']);

        $this->setColumnValues($schemeReturn, $values);

        $this->persist($schemeReturn);
    }

    protected function attemptToFormatAsBusinessCase(?string $value): ?BusinessCase
    {
        if ($value === null) {
            return null;
        }

        $map = [
            'OBC' => BusinessCase::WORKING_TOWARDS_OBC,
            'SOBC' => BusinessCase::WORKING_TOWARDS_SOBC,
            'pre-SOBC' => BusinessCase::WORKING_TOWARDS_SOBC,
            'FBC' => BusinessCase::WORKING_TOWARDS_FBC,
            'Post-FBC' => BusinessCase::POST_FBC,
            'Post FBC' => BusinessCase::POST_FBC,
        ];

        return $map[$value] ?? ($this->logger->warning('Unhandled business case', [$value]) ?? null);

    }

    protected function attemptToFormatAsBcr(?string $value): ?BenefitCostRatio
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower($value);
        return match(true) {
            1 === preg_match('/^tbc/', $value),
            1 === preg_match('/^to be confirmed/', $value),
            1 === preg_match('/^bcr will be determined/', $value),
                => (new BenefitCostRatio())->setType(BenefitCostRatioType::TBC),
            'na' === $value,
            'n/a' === $value,
                => (new BenefitCostRatio())->setType(BenefitCostRatioType::NA),
            is_numeric($value) => (new BenefitCostRatio())
                ->setType(BenefitCostRatioType::VALUE)
                ->setValue($this->attemptToFormatAsDecimal($value)),
            1 === preg_match('/^(?<val>\d+(\.\d+)?)/', $value, $matches)
                => (new BenefitCostRatio())
                    ->setType(BenefitCostRatioType::VALUE)
                    ->setValue($matches['val']),
            default => ($this->logger->warning('unhandled BCR', [$value]) ?? null),
        };
    }
}