<?php

namespace App\Command\Import\ReimportExpenses;

use App\Command\Import\AbstractSheetImporter;
use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\Enum\BusinessCase;
use App\Entity\PermissionsView;
use App\Entity\PropertyChangeLog;
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
        $pcRepository = $this->entityManager->getRepository(PropertyChangeLog::class);
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
        $schemeReturn = $return->getSchemeReturnForScheme($scheme);

        if (!$schemeReturn) {
            $this->logger->error("Scheme return not found", [$schemeIdentifier]);
            return;
        }

        unset($values['businessCase']);
        unset($values['expectedBusinessCaseApproval']);
        unset($values['benefitCostRatio']);
        unset($values['progressUpdate']);
        $values['totalCost'] = $this->attemptToFormatAsFinancial($values['totalCost'], true, ['type' => 'scheme-total-cost']);
        $values['agreedFunding'] = $this->attemptToFormatAsFinancial($values['agreedFunding'], true, ['type' => 'scheme-agreed-funding']);

        if (number_format($values['totalCost'], 2, '.', '') === $schemeReturn->getTotalCost()) {
            $this->logger->info("Total cost unchanged", [$schemeReturn->getTotalCost(), $values['totalCost'], $schemeIdentifier]);
            unset($values['totalCost']);
        } else {
            $totalCostChangeLogs = $pcRepository->findBy([
                'entityClass' => CrstsSchemeReturn::class,
                'entityId' => $schemeReturn->getId(),
                'propertyName' => 'totalCost',
            ]);
            if (!empty($totalCostChangeLogs)) {
                $this->logger->warning("Total cost changed elsewhere", [$schemeReturn->getTotalCost(), $values['totalCost'], $schemeIdentifier]);
                unset($values['totalCost']);
            }
        }

        if (number_format($values['agreedFunding'], 2, '.', '') === $schemeReturn->getAgreedFunding()) {
            $this->logger->info("Agreed funding unchanged", [$schemeReturn->getAgreedFunding(), $values['agreedFunding'], $schemeIdentifier]);
            unset($values['agreedFunding']);
        } else {
            $agreedFundingChangeLogs = $pcRepository->findBy([
                'entityClass' => CrstsSchemeReturn::class,
                'entityId' => $schemeReturn->getId(),
                'propertyName' => 'agreedFunding',
            ]);
            if (!empty($agreedFundingChangeLogs)) {
                $this->logger->warning("Agreed funding changed elsewhere", [$schemeReturn->getAgreedFunding(), $values['agreedFunding'], $schemeIdentifier]);
                unset($values['agreedFunding']);
            }
        }

        if (empty($values)) {
            $this->logger->info("Scheme return not updated", [$schemeIdentifier]);
            return;
        }

        $this->logger->warning("Scheme return values changed", [[$schemeReturn->getTotalCost(), $schemeReturn->getAgreedFunding()], $values, $schemeIdentifier]);
        $this->setColumnValues($schemeReturn, $values);
    }
}