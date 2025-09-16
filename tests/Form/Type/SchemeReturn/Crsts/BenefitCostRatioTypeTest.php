<?php

namespace App\Tests\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\BenefitCostRatioType as BenefitCostRatioTypeEnum;
use App\Entity\SchemeFund\BenefitCostRatio;
use App\Form\Type\SchemeReturn\Crsts\BenefitCostRatioType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class BenefitCostRatioTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid ratio with value' => [
                true,
                [
                    'type' => 'value',
                    'value' => '2.5'
                ],
                [ // Expected data on entity
                    'type' => BenefitCostRatioTypeEnum::VALUE,
                    'value' => '2.5'
                ]
            ],
            'Valid ratio TBC' => [
                true,
                [
                    'type' => 'tbc',
                    'value' => '123' // Value should be ignored when type is tbc
                ],
                [ // Expected data on entity
                    'type' => BenefitCostRatioTypeEnum::TBC,
                    'value' => null // Value should be null when type is not VALUE
                ]
            ],
            'Valid ratio NA' => [
                true,
                [
                    'type' => 'na',
                    'value' => '999' // Value should be ignored when type is na
                ],
                [ // Expected data on entity
                    'type' => BenefitCostRatioTypeEnum::NA,
                    'value' => null // Value should be null when type is not VALUE
                ]
            ],
            'Invalid type choice - invalid' => [
                false,
                [
                    'type' => 'invalid_type',
                    'value' => '2.5'
                ],
                [] // No expected data as form is invalid
            ],
            'Empty type - valid (no constraint without validation group)' => [
                true,
                [
                    'type' => '',
                    'value' => '2.5'
                ],
                [ // Expected data on entity
                    'type' => null,
                    'value' => null // When type is empty/null, value should also be null
                ]
            ],
            'Value type with empty value - valid (no constraint without validation group)' => [
                true,
                [
                    'type' => 'value',
                    'value' => ''
                ],
                [ // Expected data on entity
                    'type' => BenefitCostRatioTypeEnum::VALUE,
                    'value' => '' // Empty string is preserved when type is VALUE
                ]
            ],
            'Value type with non-numeric value - valid (accepted as string)' => [
                true,
                [
                    'type' => 'value',
                    'value' => 'not_a_number'
                ],
                [ // Expected data on entity
                    'type' => BenefitCostRatioTypeEnum::VALUE,
                    'value' => 'not_a_number' // String value is preserved (validation would fail with proper validation group)
                ]
            ],
            'Value type with decimal number' => [
                true,
                [
                    'type' => 'value',
                    'value' => '10.75'
                ],
                [ // Expected data on entity
                    'type' => BenefitCostRatioTypeEnum::VALUE,
                    'value' => '10.75'
                ]
            ],
            'NA type with value in form - value should be cleared' => [
                true,
                [
                    'type' => 'na',
                    'value' => '500.25'
                ],
                [ // Expected data on entity
                    'type' => BenefitCostRatioTypeEnum::NA,
                    'value' => null // Data mapper should clear value when type is not VALUE
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        $benefitCostRatio = new BenefitCostRatio();

        $form = $this->formFactory->create(BenefitCostRatioType::class, $benefitCostRatio, [
            'csrf_protection' => false,
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }

    /**
     * @dataProvider dataForm
     */
    public function testDataMapper(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        // Skip invalid form cases
        if (!$expectedToBeValid || empty($expectedData)) {
            $this->assertTrue(true, 'Skipping data mapper test for invalid form');
            return;
        }

        $benefitCostRatio = new BenefitCostRatio();

        $form = $this->formFactory->create(BenefitCostRatioType::class, $benefitCostRatio, [
            'csrf_protection' => false,
        ]);
        $form->submit($formData);

        // Ensure form is valid before checking data
        $this->assertTrue($form->isValid(), 'Form should be valid for data mapper test');

        // Get the updated entity
        $updatedEntity = $form->getData();
        $this->assertInstanceOf(BenefitCostRatio::class, $updatedEntity);

        // Check that the data mapper correctly set the values
        $this->assertEquals(
            $expectedData['type'],
            $updatedEntity->getType(),
            'Type should be correctly mapped to entity'
        );

        $this->assertEquals(
            $expectedData['value'],
            $updatedEntity->getValue(),
            'Value should be correctly mapped to entity based on type'
        );

        // Additional assertion: when type is not VALUE, value should always be null
        if ($expectedData['type'] !== null && $expectedData['type'] !== BenefitCostRatioTypeEnum::VALUE) {
            $this->assertNull(
                $updatedEntity->getValue(),
                'Value should be null when type is not VALUE'
            );
        }
    }
}