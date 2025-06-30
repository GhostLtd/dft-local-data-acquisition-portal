<?php

namespace App\Utility\SignoffHelper;

use App\Config\ExpenseDivision\TableConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\RowGroupInterface;
use App\Entity\Enum\ExpenseType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Repository\SchemeReturn\SchemeReturnRepository;
use App\Utility\CrstsHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CrstsSignoffHelper implements SignoffHelperInterface
{
    protected bool $useAdminLinks;
    protected TableConfiguration $tableConfiguration;

    public function __construct(
        protected SchemeReturnRepository $schemeReturnRepository,
        protected UrlGeneratorInterface  $urlGenerator,
    ) {
        $this->useAdminLinks = false;
    }

    public function setUseAdminLinks(bool $useAdminLinks): static
    {
        $this->useAdminLinks = $useAdminLinks;
        return $this;
    }

    public function getSignoffEligibilityStatus(FundReturn|SchemeReturn $return, bool $useAdminLinks=false): SignoffEligibilityStatus
    {
        $generator = $this->getGenerator($return);
        $problems = iterator_to_array($generator, false);

        return new SignoffEligibilityStatus(isEligible: empty($problems), problems: $problems);
    }

    public function hasSignoffEligibilityProblems(FundReturn|SchemeReturn $return): bool
    {
        return $this->getGenerator($return)->valid(); // i.e. is there at least one problem?
    }

    protected function getGenerator(FundReturn|SchemeReturn $return): \Generator
    {
        if ($return instanceof CrstsFundReturn) {
            $this->initialiseTableHelper($return);
            return $this->getFundReturnSignoffEligibilityProblems($return);
        } else if ($return instanceof CrstsSchemeReturn) {
            $this->initialiseTableHelper($return->getFundReturn());
            return $this->getSchemeReturnSignoffEligibilityProblems($return);
        } else {
            throw new \RuntimeException("Expected " . CrstsFundReturn::class . " or " . CrstsSchemeReturn::class . ", got " . $return::class);
        }
    }

    protected function initialiseTableHelper(CrstsFundReturn $return): void
    {
        $this->tableConfiguration = CrstsHelper::getFundExpensesTable($return->getYear(), $return->getQuarter(), hideForecastAndActual: true);
    }

    /**
     * @return \Generator<EligibilityProblem>
     */
    protected function getFundReturnSignoffEligibilityProblems(CrstsFundReturn $return): \Generator
    {
        // Look for missing forecast, non-baseline, expense figures
        // (These are identified by matching ExpenseEntries being missing from the database)
        $expenseTypes = $this->getNonBaselineExpenseTypes();

        $fundReturnRoute = $this->useAdminLinks ? 'admin_fund_return' : 'app_fund_return';

        foreach($this->tableConfiguration->getDivisionConfigurations() as $divisionConfiguration) {
            foreach($divisionConfiguration->getColumnConfigurations() as $columnConfiguration) {
                if ($columnConfiguration->isForecast()) {
                    foreach($expenseTypes as $expenseType) {
                        $matchingExpense = null;
                        foreach($return->getExpenses() as $expense) {
                            if (
                                $expense->getDivision() === $divisionConfiguration->getKey() &&
                                $expense->getColumn() === $columnConfiguration->getKey() &&
                                $expense->getType() === $expenseType
                            ) {
                                $matchingExpense = true;
                            }
                        }

                        if (!$matchingExpense) {
                            yield new EligibilityProblem(
                                EligibilityProblemType::MISSING_FORECAST,
                                'eligibility.fund_return.missing_forecast',
                                messageParameters: [
                                    'division' => $divisionConfiguration->getLabel(),
                                ],
                                url: $this->urlGenerator->generate($fundReturnRoute, ['fundReturnId' => $return->getId()]).'#expenses-'.$divisionConfiguration->getKey(),
                            );
                            break 2;
                        }
                    }
                }
            }
        }

        // Check for scheme return problems
        foreach($return->getSchemeReturns() as $schemeReturn) {
            yield from $this->getSchemeReturnSignoffEligibilityProblems($schemeReturn);
        }
    }

    /**
     * @return \Generator<EligibilityProblem>
     */
    protected function getSchemeReturnSignoffEligibilityProblems(CrstsSchemeReturn $return): \Generator
    {
        if ($this->schemeReturnRepository->cachedFindPointWhereReturnBecameNonEditable($return) !== null) {
            // This scheme was previously marked as merged/split/complete and so is no longer editable.
            // As such, we don't want to flag any problems related to this return.
            return;
        }

        $onTrackRating = $return->getOnTrackRating();
        $schemeReturnRoute = $this->useAdminLinks ? 'admin_scheme_return' : 'app_scheme_return';

        if ($onTrackRating === null) {
            $scheme = $return->getScheme();

            yield new EligibilityProblem(
                type: EligibilityProblemType::ON_TRACK_RATING_EMPTY,
                message: 'eligibility.scheme_return.name',
                messageParameters: [
                    'schemeName' => $scheme->getName(),
                ],
                url: $this->urlGenerator->generate($schemeReturnRoute, [
                    'fundReturnId' => $return->getFundReturn()->getId(),
                    'schemeId' => $scheme->getId(),
                ]).'#milestone_progress',
            );
        }
    }

    /**
     * @return array<ExpenseType>
     */
    protected function getNonBaselineExpenseTypes(): array
    {
        $expenseTypes = array_merge(...array_map(
            fn(CategoryConfiguration $category) => $category->getExpenseTypes(),
            array_filter($this->tableConfiguration->getRowGroupConfigurations(), fn(RowGroupInterface $r) => $r instanceof CategoryConfiguration)
        ));

        return array_filter($expenseTypes, fn(ExpenseType $e) => !$e->isBaseline());
    }

    public function supports(SchemeReturn|FundReturn $return): bool
    {
        return
            $return instanceof CrstsFundReturn ||
            $return instanceof CrstsSchemeReturn;
    }
}
