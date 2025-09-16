<?php

namespace App\Tests\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\BusinessCase;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\SchemeReturn\Crsts\MilestoneBusinessCaseType;
use App\Tests\DataFixtures\Form\Type\SchemeReturn\Crsts\MilestoneBusinessCaseTypeFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use Doctrine\Common\DataFixtures\ReferenceRepository;

class MilestoneBusinessCaseTypeTest extends AbstractKernelTypeTest
{
    private ReferenceRepository $referenceRepository;
    public function dataForm(): array
    {
        return [
            'Valid business case with date' => [
                true,
                [
                    'businessCase' => 'working_towards_obc',
                    'expectedBusinessCaseApproval' => [
                        'day' => '25',
                        'month' => '12',
                        'year' => '2024'
                    ]
                ],
                [
                    'businessCase' => BusinessCase::WORKING_TOWARDS_OBC,
                    'expectedBusinessCaseApproval' => '2024-12-25'
                ]
            ],
            'Valid business case not applicable' => [
                true,
                [
                    'businessCase' => 'not_applicable',
                    'expectedBusinessCaseApproval' => [
                        'day' => '25',
                        'month' => '12',
                        'year' => '2024'
                    ]
                ],
                [
                    'businessCase' => BusinessCase::NOT_APPLICABLE,
                    'expectedBusinessCaseApproval' => null
                ]
            ],
            'Empty business case - invalid' => [
                false,
                [
                    'businessCase' => '',
                    'expectedBusinessCaseApproval' => [
                        'day' => '25',
                        'month' => '12',
                        'year' => '2024'
                    ]
                ],
                []
            ],
            'Invalid business case choice - invalid' => [
                false,
                [
                    'businessCase' => 'invalid_choice',
                    'expectedBusinessCaseApproval' => [
                        'day' => '25',
                        'month' => '12',
                        'year' => '2024'
                    ]
                ],
                []
            ]
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([MilestoneBusinessCaseTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsSchemeReturn $schemeReturn */
        $schemeReturn = $this->referenceRepository->getReference(MilestoneBusinessCaseTypeFixtures::CRSTS_SCHEME_RETURN, CrstsSchemeReturn::class);

        $form = $this->formFactory->create(MilestoneBusinessCaseType::class, $schemeReturn, [
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
        if (!$expectedToBeValid || empty($expectedData)) {
            $this->assertTrue(true, 'Skipping data mapper test for invalid form');
            return;
        }

        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([MilestoneBusinessCaseTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsSchemeReturn $schemeReturn */
        $schemeReturn = $this->referenceRepository->getReference(MilestoneBusinessCaseTypeFixtures::CRSTS_SCHEME_RETURN, CrstsSchemeReturn::class);

        $form = $this->formFactory->create(MilestoneBusinessCaseType::class, $schemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);

        $this->assertTrue($form->isValid(), 'Form should be valid for data mapper test');

        $updatedEntity = $form->getData();
        $this->assertInstanceOf(CrstsSchemeReturn::class, $updatedEntity);

        $this->assertEquals(
            $expectedData['businessCase'],
            $updatedEntity->getBusinessCase(),
            'Business case should be correctly mapped'
        );

        if ($expectedData['expectedBusinessCaseApproval'] === null) {
            $this->assertNull(
                $updatedEntity->getExpectedBusinessCaseApproval(),
                'Expected business case approval should be null when business case is NOT_APPLICABLE'
            );
        } else {
            $this->assertNotNull(
                $updatedEntity->getExpectedBusinessCaseApproval(),
                'Expected business case approval should not be null when business case requires it'
            );
        }
    }
}