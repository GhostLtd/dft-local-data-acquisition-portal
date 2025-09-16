<?php

namespace App\Tests\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\BenefitCostRatioType as BenefitCostRatioTypeEnum;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeFund\BenefitCostRatio;
use App\Form\Type\SchemeReturn\Crsts\OverallFundingType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class OverallFundingTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid funding amounts' => [
                true,
                [
                    'totalCost' => '1000000',
                    'agreedFunding' => '750000',
                    'benefitCostRatio' => [
                        'type' => 'value',
                        'value' => '2.5'
                    ]
                ],
                [
                    'totalCost' => '1000000',
                    'agreedFunding' => '750000',
                    'benefitCostRatio' => [
                        'type' => BenefitCostRatioTypeEnum::VALUE,
                        'value' => '2.5'
                    ]
                ]
            ],
            'Valid funding with commas' => [
                true,
                [
                    'totalCost' => '1,000,000',
                    'agreedFunding' => '750,000',
                    'benefitCostRatio' => [
                        'type' => 'na',
                        'value' => ''
                    ]
                ],
                [
                    'totalCost' => '1000000',
                    'agreedFunding' => '750000',
                    'benefitCostRatio' => [
                        'type' => BenefitCostRatioTypeEnum::NA,
                        'value' => null
                    ]
                ]
            ],
            'Empty total cost - invalid' => [
                false,
                [
                    'totalCost' => '',
                    'agreedFunding' => '750000',
                    'benefitCostRatio' => [
                        'type' => 'na',
                        'value' => ''
                    ]
                ],
                []
            ],
            'Invalid numeric format - invalid' => [
                false,
                [
                    'totalCost' => 'invalid',
                    'agreedFunding' => '750000',
                    'benefitCostRatio' => [
                        'type' => 'na',
                        'value' => ''
                    ]
                ],
                []
            ],
            'Negative values - valid (no constraint against negatives)' => [
                true,
                [
                    'totalCost' => '-1000',
                    'agreedFunding' => '750000',
                    'benefitCostRatio' => [
                        'type' => 'na',
                        'value' => ''
                    ]
                ],
                [
                    'totalCost' => '-1000',
                    'agreedFunding' => '750000',
                    'benefitCostRatio' => [
                        'type' => BenefitCostRatioTypeEnum::NA,
                        'value' => null
                    ]
                ]
            ],
            'Multiple commas in numbers' => [
                true,
                [
                    'totalCost' => '1,234,567.89',
                    'agreedFunding' => '999,999.99',
                    'benefitCostRatio' => [
                        'type' => 'tbc',
                        'value' => '123'
                    ]
                ],
                [
                    'totalCost' => '1234567.89',
                    'agreedFunding' => '999999.99',
                    'benefitCostRatio' => [
                        'type' => BenefitCostRatioTypeEnum::TBC,
                        'value' => null
                    ]
                ]
            ],
            'Invalid benefit cost ratio value - invalid' => [
                false,
                [
                    'totalCost' => '1000000',
                    'agreedFunding' => '750000',
                    'benefitCostRatio' => [
                        'type' => 'value',
                        'value' => 'banana'
                    ]
                ],
                []
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setBenefitCostRatio(new BenefitCostRatio());

        $form = $this->formFactory->create(OverallFundingType::class, $schemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
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

        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setBenefitCostRatio(new BenefitCostRatio());

        $form = $this->formFactory->create(OverallFundingType::class, $schemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);

        // Ensure form is valid before checking data
        $this->assertTrue($form->isValid(), 'Form should be valid for data mapper test');

        // Get the updated entity
        $updatedEntity = $form->getData();
        $this->assertInstanceOf(CrstsSchemeReturn::class, $updatedEntity);

        // Check that the data mapper correctly removed commas from numeric fields
        $this->assertEquals(
            $expectedData['totalCost'],
            $updatedEntity->getTotalCost(),
            'Total cost should have commas removed by data mapper'
        );

        $this->assertEquals(
            $expectedData['agreedFunding'],
            $updatedEntity->getAgreedFunding(),
            'Agreed funding should have commas removed by data mapper'
        );

        // Check BenefitCostRatio data mapping
        $benefitCostRatio = $updatedEntity->getBenefitCostRatio();
        $this->assertInstanceOf(BenefitCostRatio::class, $benefitCostRatio);

        $this->assertEquals(
            $expectedData['benefitCostRatio']['type'],
            $benefitCostRatio->getType(),
            'BenefitCostRatio type should be correctly mapped'
        );

        $this->assertEquals(
            $expectedData['benefitCostRatio']['value'],
            $benefitCostRatio->getValue(),
            'BenefitCostRatio value should be correctly mapped based on type'
        );
    }
}