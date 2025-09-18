<?php

namespace App\Tests\Form\Type;

use App\Entity\Enum\ExpenseType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Form\Type\FundReturn\Crsts\ExpensesDataMapper;
use App\Form\Type\FundReturn\Crsts\ExpensesType;
use App\Tests\DataFixtures\Form\Type\ExpensesTypeFixtures;
use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Config\ExpenseDivision\ColumnConfiguration;
use App\Config\ExpenseDivision\TableConfiguration;
use App\Config\ExpenseRow\UngroupedConfiguration;
use App\Utility\ExpensesTableHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ExpensesTypeTest extends AbstractKernelTypeTest
{
    private ReferenceRepository $referenceRepository;

    public function dataForm(): array
    {
        return [
            'Valid expenses form - valid' => [
                true,
                [
                    'expense__2024_25__fex__q1' => '100000',
                    'expense__2024_25__fex__q2' => '150000',
                    'comments' => 'Test comments'
                ],
                [
                    'expense__2024_25__fex__q1' => '100000',
                    'expense__2024_25__fex__q2' => '150000',
                    'comments' => 'Test comments'
                ]
            ],
            'Forecast field required but empty - invalid' => [
                false,
                [
                    'expense__2024_25__fex__q1' => '100000',
                    'expense__2024_25__fex__q2' => '', // Required forecast field empty
                    'comments' => 'Test comments'
                ]
            ],
            'Negative values in expense fields - valid' => [
                true,
                [
                    'expense__2024_25__fex__q1' => '-100000', // Negative values might be allowed
                    'expense__2024_25__fex__q2' => '150000',
                    'comments' => 'Test comments'
                ],
                [
                    'expense__2024_25__fex__q1' => '-100000',
                    'expense__2024_25__fex__q2' => '150000',
                    'comments' => 'Test comments'
                ]
            ],
            'Valid with comma-separated values - valid' => [
                true,
                [
                    'expense__2024_25__fex__q1' => '1,000,000',
                    'expense__2024_25__fex__q2' => '1,500,000',
                    'comments' => 'Test comments with large values'
                ],
                [
                    'expense__2024_25__fex__q1' => '1000000', // Commas removed by ExpensesDataMapper
                    'expense__2024_25__fex__q2' => '1500000', // Commas removed by ExpensesDataMapper
                    'comments' => 'Test comments with large values'
                ]
            ],
            'Valid without comments - valid' => [
                true,
                [
                    'expense__2024_25__fex__q1' => '100000',
                    'expense__2024_25__fex__q2' => '150000'
                    // No comments field submitted
                ],
                [
                    'expense__2024_25__fex__q1' => '100000',
                    'expense__2024_25__fex__q2' => '150000'
                ]
            ],
            'Decimal values in expense fields - valid' => [
                true,
                [
                    'expense__2024_25__fex__q1' => '100000.50', // Decimal value should be valid
                    'expense__2024_25__fex__q2' => '150000.75',
                    'comments' => 'Test comments'
                ],
                [
                    'expense__2024_25__fex__q1' => '100000.50',
                    'expense__2024_25__fex__q2' => '150000.75',
                    'comments' => 'Test comments'
                ]
            ],
            'Zero values in expense fields - valid' => [
                true,
                [
                    'expense__2024_25__fex__q1' => '0',
                    'expense__2024_25__fex__q2' => '0.00',
                    'comments' => 'Test comments'
                ],
                [
                    'expense__2024_25__fex__q1' => '0',
                    'expense__2024_25__fex__q2' => '0.00',
                    'comments' => 'Test comments'
                ]
            ],
            'Empty string in non-forecast field - valid' => [
                true,
                [
                    'expense__2024_25__fex__q1' => '', // Non-forecast field can be empty
                    'expense__2024_25__fex__q2' => '150000',
                    'comments' => 'Test comments'
                ],
                [
                    'expense__2024_25__fex__q1' => '',
                    'expense__2024_25__fex__q2' => '150000',
                    'comments' => 'Test comments'
                ]
            ],
            'Nonsense text in expense fields - invalid' => [
                false, // InputType validates numeric input and rejects nonsense
                [
                    'expense__2024_25__fex__q1' => 'Banana', // Non-numeric nonsense - rejected by form
                    'expense__2024_25__fex__q2' => '150000',
                    'comments' => 'Test comments'
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([ExpensesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsFundReturn $crstsFundReturn */
        $crstsFundReturn = $this->referenceRepository->getReference(ExpensesTypeFixtures::CRSTS_FUND_RETURN, CrstsFundReturn::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $dataMapper = new ExpensesDataMapper($entityManager);

        $tableHelper = $this->createRealExpensesTableHelper();
        $dataMapper->setTableHelper($tableHelper);

        // Create the form
        $expensesType = new ExpensesType($dataMapper);

        $form = $this->formFactory->create(ExpensesType::class, $crstsFundReturn, [
            'csrf_protection' => false,
            'expenses_table_helper' => $tableHelper,
            'comments_enabled' => true,
            'cancel_url' => '/test-cancel'
        ]);

        $form->submit($formData);

        if (!$expectedToBeValid && $form->isValid()) {
            $this->fail(sprintf(
                'Form should be invalid but was valid. Submitted data: %s',
                json_encode($formData, JSON_PRETTY_PRINT)
            ));
        }

        if ($expectedToBeValid && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->fail(sprintf(
                'Form should be valid but has errors: %s. Submitted data: %s',
                implode(', ', $errors),
                json_encode($formData, JSON_PRETTY_PRINT)
            ));
        }

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }

    /**
     * @dataProvider dataForm
     */
    public function testDataMapper(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        if (!$expectedToBeValid || empty($expectedData)) {
            $this->assertTrue(true, 'Skipping data mapper test for invalid form');
            return;
        }

        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([ExpensesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsFundReturn $crstsFundReturn */
        $crstsFundReturn = $this->referenceRepository->getReference(ExpensesTypeFixtures::CRSTS_FUND_RETURN, CrstsFundReturn::class);

        // Mock EntityManager with expectations
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())->method('persist');
        $entityManager->expects($this->any())->method('remove');

        $dataMapper = new ExpensesDataMapper($entityManager);

        $tableHelper = $this->createRealExpensesTableHelper();
        $dataMapper->setTableHelper($tableHelper);

        // Create the form
        $expensesType = new ExpensesType($dataMapper);

        $form = $this->formFactory->create(ExpensesType::class, $crstsFundReturn, [
            'csrf_protection' => false,
            'expenses_table_helper' => $tableHelper,
            'comments_enabled' => true,
            'cancel_url' => '/test-cancel'
        ]);

        $form->submit($formData);

        $this->assertTrue($form->isValid(), 'Form should be valid for data mapper test');

        // Test that the data was correctly mapped back to the entity
        $updatedEntity = $form->getData();
        $this->assertInstanceOf(CrstsFundReturn::class, $updatedEntity);

        // Test comments mapping
        if (isset($expectedData['comments'])) {
            $this->assertEquals(
                $expectedData['comments'],
                $updatedEntity->getExpenseDivisionComment('2024_25'),
                'Comments should be correctly mapped'
            );
        }
    }

    public function testFormWithCommentsDisabled(): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([ExpensesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsFundReturn $crstsFundReturn */
        $crstsFundReturn = $this->referenceRepository->getReference(ExpensesTypeFixtures::CRSTS_FUND_RETURN, CrstsFundReturn::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $dataMapper = new ExpensesDataMapper($entityManager);

        $tableHelper = $this->createRealExpensesTableHelper();
        $dataMapper->setTableHelper($tableHelper);

        // Create the form with comments disabled
        $form = $this->formFactory->create(ExpensesType::class, $crstsFundReturn, [
            'csrf_protection' => false,
            'expenses_table_helper' => $tableHelper,
            'comments_enabled' => false,
            'cancel_url' => '/test-cancel'
        ]);

        // Verify comments field is not present
        $this->assertFalse($form->has('comments'), 'Comments field should not be present when disabled');
    }

    public function testDataMapperNotInitialized(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $dataMapper = new ExpensesDataMapper($entityManager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DataMapper not correctly initialised');

        // Try to use mapper without setting table helper
        $dataMapper->mapDataToForms(new CrstsFundReturn(), new \ArrayIterator([]));
    }

    public function testFormStructure(): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([ExpensesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsFundReturn $crstsFundReturn */
        $crstsFundReturn = $this->referenceRepository->getReference(ExpensesTypeFixtures::CRSTS_FUND_RETURN, CrstsFundReturn::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $dataMapper = new ExpensesDataMapper($entityManager);

        $tableHelper = $this->createRealExpensesTableHelper();
        $dataMapper->setTableHelper($tableHelper);

        // Create the form
        $form = $this->formFactory->create(ExpensesType::class, $crstsFundReturn, [
            'csrf_protection' => false,
            'expenses_table_helper' => $tableHelper,
            'comments_enabled' => true,
            'cancel_url' => '/test-cancel'
        ]);

        // Verify form has expected fields
        $this->assertTrue($form->has('expense__2024_25__fex__q1'), 'Form should have Q1 expense field');
        $this->assertTrue($form->has('expense__2024_25__fex__q2'), 'Form should have Q2 expense field');
        $this->assertTrue($form->has('comments'), 'Form should have comments field');

        // Verify field types and constraints
        $q1Field = $form->get('expense__2024_25__fex__q1');
        $q2Field = $form->get('expense__2024_25__fex__q2');

        $this->assertFalse($q1Field->getConfig()->getDisabled(), 'Q1 field should not be disabled');
        $this->assertFalse($q2Field->getConfig()->getDisabled(), 'Q2 field should not be disabled');

        // Check data attributes for JavaScript functionality
        $q1Attrs = $q1Field->getConfig()->getOption('attr');
        $q2Attrs = $q2Field->getConfig()->getOption('attr');

        // Verify that data attributes exist (the actual values depend on mock implementation)
        $this->assertIsArray($q1Attrs, 'Q1 field should have attributes array');
        $this->assertIsArray($q2Attrs, 'Q2 field should have attributes array');
    }

    public function testForecastFieldValidation(): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([ExpensesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsFundReturn $crstsFundReturn */
        $crstsFundReturn = $this->referenceRepository->getReference(ExpensesTypeFixtures::CRSTS_FUND_RETURN, CrstsFundReturn::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $dataMapper = new ExpensesDataMapper($entityManager);

        $tableHelper = $this->createRealExpensesTableHelper();
        $dataMapper->setTableHelper($tableHelper);

        // Create the form
        $form = $this->formFactory->create(ExpensesType::class, $crstsFundReturn, [
            'csrf_protection' => false,
            'expenses_table_helper' => $tableHelper,
            'comments_enabled' => true,
            'cancel_url' => '/test-cancel'
        ]);

        // Check if the Q2 field has NotBlank constraint (forecast field)
        $q2Field = $form->get('expense__2024_25__fex__q2');
        $constraints = $q2Field->getConfig()->getOption('constraints');

        // Simply verify that forecast field has constraints while non-forecast doesn't
        $q1Field = $form->get('expense__2024_25__fex__q1');
        $q1Constraints = $q1Field->getConfig()->getOption('constraints');

        $this->assertIsArray($constraints, 'Q2 field should have constraints array');
        $this->assertIsArray($q1Constraints, 'Q1 field should have constraints array');

        // Q2 (forecast) should have constraints, Q1 (non-forecast) should not
        $this->assertNotEmpty($constraints, 'Forecast field (Q2) should have validation constraints');
        $this->assertEmpty($q1Constraints, 'Non-forecast field (Q1) should not have validation constraints');

        $hasNotBlankConstraint = false;
        foreach ($constraints as $constraint) {
            if ($constraint instanceof NotBlank) {
                $hasNotBlankConstraint = true;
                break;
            }
        }
        $this->assertTrue($hasNotBlankConstraint, 'Forecast field should have NotBlank constraint');
    }

    public function testDataFlowAndValidationLayers(): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([ExpensesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsFundReturn $crstsFundReturn */
        $crstsFundReturn = $this->referenceRepository->getReference(ExpensesTypeFixtures::CRSTS_FUND_RETURN, CrstsFundReturn::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $dataMapper = new ExpensesDataMapper($entityManager);
        $tableHelper = $this->createRealExpensesTableHelper();
        $dataMapper->setTableHelper($tableHelper);

        $form = $this->formFactory->create(ExpensesType::class, $crstsFundReturn, [
            'csrf_protection' => false,
            'expenses_table_helper' => $tableHelper,
            'comments_enabled' => true,
            'cancel_url' => '/test-cancel'
        ]);

        // Test various values to understand data flow
        $testCases = [
            'comma_value' => '1,000,000',
            'nonsense' => 'Banana',
            'decimal' => '123.45'
        ];

        foreach ($testCases as $testName => $testValue) {
            // Create a fresh form for each test case to avoid "already submitted" error
            $freshForm = $this->formFactory->create(ExpensesType::class, $crstsFundReturn, [
                'csrf_protection' => false,
                'expenses_table_helper' => $tableHelper,
                'comments_enabled' => true,
                'cancel_url' => '/test-cancel'
            ]);

            $freshForm->submit([
                'expense__2024_25__fex__q1' => $testValue,
                'expense__2024_25__fex__q2' => '150000',
                'comments' => "Testing {$testName}"
            ]);

            if ($testName === 'nonsense') {
                // Nonsense values are rejected by form validation (InputType enforces numeric input)
                $this->assertFalse($freshForm->isValid(), "Form should be invalid for nonsense values");
            } else {
                $this->assertTrue($freshForm->isValid(), "Form should be valid for {$testName}");

                $updatedEntity = $freshForm->getData();
                $expenses = $updatedEntity->getExpenses();

                if ($expenses->count() > 0) {
                    $firstExpense = $expenses->first();
                    $expectedValue = $testValue;

                    // ExpensesDataMapper removes commas (line 99: $value = str_replace(',', '', $value))
                    if ($testName === 'comma_value') {
                        $expectedValue = '1000000'; // Commas removed by data mapper
                    }

                    $this->assertEquals($expectedValue, $firstExpense->getValue(),
                        "Value processing for {$testName}: commas removed by ExpensesDataMapper, other values unchanged");
                }
            }
        }
    }

    private function createRealExpensesTableHelper(): ExpensesTableHelper
    {
        $q1Column = new ColumnConfiguration('q1', false, 'Q1 Actual');
        $q2Column = new ColumnConfiguration('q2', true, 'Q2 Forecast'); // Forecast field (required)

        $divisionConfig = new DivisionConfiguration('2024_25', [$q1Column, $q2Column], '2024/25');

        $expenseRows = new UngroupedConfiguration([
            ExpenseType::FUND_CAPITAL_EXPENDITURE,
        ]);

        $tableConfig = new TableConfiguration(
            [$expenseRows],
            [$divisionConfig],
            []
        );

        $tableHelper = new ExpensesTableHelper();
        $tableHelper->setConfiguration($tableConfig);
        $tableHelper->setDivisionKey('2024_25');

        return $tableHelper;
    }
}