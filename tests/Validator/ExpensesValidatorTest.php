<?php

namespace App\Tests\Validator;

use App\Entity\Enum\ExpenseType;
use App\Entity\ExpenseEntry;
use App\Entity\ExpensesContainerInterface;
use App\Validator\ExpensesValidator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ExpensesValidatorTest extends TestCase
{
    /** @var ExecutionContext&MockObject */
    private ExecutionContext $context;
    /** @var ValidatorInterface&MockObject */
    private ValidatorInterface $validator;
    /** @var ContextualValidatorInterface&MockObject */
    private ContextualValidatorInterface $contextualValidator;
    /** @var ConstraintViolationBuilderInterface&MockObject */
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContext::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->contextualValidator = $this->createMock(ContextualValidatorInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
    }

    /**
     * @dataProvider validationScenarioProvider
     */
    public function testValidateWithDifferentScenarios(
        array $expenseData,
        int $expectedValidatorCalls,
        array $expectedPaths,
        array $expectedValues
    ): void {
        $expenses = array_map(fn($data) => $this->createExpenseMock(...$data), $expenseData);
        $container = $this->createMock(ExpensesContainerInterface::class);
        $container->method('getExpenses')->willReturn(new ArrayCollection($expenses));

        if ($expectedValidatorCalls > 0) {
            $this->setupValidatorMockExpectations($expectedValidatorCalls, $expectedPaths, $expectedValues);
        } else {
            $this->context->expects($this->never())
                ->method('getValidator');
        }

        ExpensesValidator::validate($container, $this->context, null);
    }

    public function testValidateWithInvalidValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected argument of type "App\Entity\ExpensesContainerInterface", "string" given');

        ExpensesValidator::validate('invalid', $this->context, null);
    }



    public function validationScenarioProvider(): array
    {
        return [
            'valid expenses' => [
                [['2023', ExpenseType::FUND_CAPITAL_EXPENDITURE, 'Q1', '1234.56'], ['2024', ExpenseType::FUND_RESOURCE_EXPENDITURE, 'Q2', '9876.54']],
                2,
                ['expense__2023__fex__Q1', 'expense__2024__fre__Q2'],
                ['1234.56', '9876.54']
            ],
            'null value expense' => [
                [['2023', ExpenseType::FUND_CAPITAL_EXPENDITURE, 'Q1', null]],
                1,
                ['expense__2023__fex__Q1'],
                [null]
            ],
            'empty expenses' => [
                [],
                0,
                [],
                []
            ],
            'single capital expense' => [
                [['2023', ExpenseType::FUND_CAPITAL_EXPENDITURE, 'total', '100.00']],
                1,
                ['expense__2023__fex__total'],
                ['100.00']
            ],
            'single resource expense' => [
                [['2023', ExpenseType::FUND_RESOURCE_EXPENDITURE, 'total', '200.00']],
                1,
                ['expense__2023__fre__total'],
                ['200.00']
            ],
            'mixed values with nulls' => [
                [['2023', ExpenseType::FUND_CAPITAL_EXPENDITURE, 'Q1', '100.00'], ['2023', ExpenseType::FUND_RESOURCE_EXPENDITURE, 'Q2', null], ['2024', ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION, 'Q3', '300.00']],
                3,
                ['expense__2023__fex__Q1', 'expense__2023__fre__Q2', 'expense__2024__flc__Q3'],
                ['100.00', null, '300.00']
            ]
        ];
    }

    private function createExpenseMock(string $division, ExpenseType $type, string $column, ?string $value): ExpenseEntry
    {
        $expense = $this->createMock(ExpenseEntry::class);
        $expense->method('getDivision')->willReturn($division);
        $expense->method('getType')->willReturn($type);
        $expense->method('getColumn')->willReturn($column);
        $expense->method('getValue')->willReturn($value);
        return $expense;
    }

    private function setupValidatorMockExpectations(int $callCount, array $paths, array $values): void
    {
        $this->context->expects($this->exactly($callCount))
            ->method('getValidator')
            ->willReturn($this->validator);

        $this->validator->expects($this->exactly($callCount))
            ->method('inContext')
            ->with($this->context)
            ->willReturn($this->contextualValidator);

        $pathCallCount = 0;
        $this->contextualValidator->expects($this->exactly($callCount))
            ->method('atPath')
            ->willReturnCallback(function($path) use ($paths, &$pathCallCount) {
                $this->assertSame($paths[$pathCallCount], $path);
                $pathCallCount++;
                return $this->contextualValidator;
            });

        $validateCallCount = 0;
        $this->contextualValidator->expects($this->exactly($callCount))
            ->method('validate')
            ->willReturnCallback(function($value, $constraints, $groups) use ($values, &$validateCallCount) {
                $this->assertSame($values[$validateCallCount], $value);
                $this->assertSame(['Default'], $groups);
                $validateCallCount++;
                return $this->contextualValidator; // Return the mock for method chaining
            });
    }
}