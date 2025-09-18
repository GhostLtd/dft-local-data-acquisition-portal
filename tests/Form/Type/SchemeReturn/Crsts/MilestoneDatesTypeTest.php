<?php

namespace App\Tests\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\SchemeReturn\Crsts\MilestoneDatesType;
use App\Tests\DataFixtures\Form\Type\SchemeReturn\Crsts\MilestoneDatesTypeFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;
use Doctrine\Common\DataFixtures\ReferenceRepository;

class MilestoneDatesTypeTest extends AbstractKernelTypeTest
{
    private ReferenceRepository $referenceRepository;

    public function dataCDELForm(): array
    {
        return [
            'CDEL - Development only with all dev milestones - valid' => [
                true,
                [
                    'developmentOnly' => 'true',
                    'start_development' => ['day' => '15', 'month' => '03', 'year' => '2024'],
                    'end_development' => ['day' => '30', 'month' => '06', 'year' => '2024'],
                ],
                [
                    'developmentOnly' => true,
                    'start_development' => '2024-03-15',
                    'end_development' => '2024-06-30',
                ]
            ],
            'CDEL - Not development only with all milestones - valid' => [
                true,
                [
                    'developmentOnly' => 'false',
                    'start_development' => ['day' => '15', 'month' => '01', 'year' => '2024'],
                    'end_development' => ['day' => '30', 'month' => '03', 'year' => '2024'],
                    'nonDevelopmentalMilestones' => [
                        'start_construction' => ['day' => '01', 'month' => '04', 'year' => '2024'],
                        'end_construction' => ['day' => '31', 'month' => '12', 'year' => '2025'],
                        'final_delivery' => ['day' => '28', 'month' => '02', 'year' => '2026'],
                    ]
                ],
                [
                    'developmentOnly' => false,
                    'start_development' => '2024-01-15',
                    'end_development' => '2024-03-30',
                    'start_construction' => '2024-04-01',
                    'end_construction' => '2025-12-31',
                    'final_delivery' => '2026-02-28',
                ]
            ],
            'CDEL - Development only missing dev milestone - invalid' => [
                false,
                [
                    'developmentOnly' => 'true',
                    'start_development' => ['day' => '15', 'month' => '03', 'year' => '2024'],
                    // Missing end_development
                ]
            ],
        ];
    }

    public function dataRDELForm(): array
    {
        return [
            'RDEL - Development only with all dev milestones - valid' => [
                true,
                [
                    'developmentOnly' => 'true',
                    'start_development' => ['day' => '15', 'month' => '03', 'year' => '2024'],
                    'end_development' => ['day' => '30', 'month' => '06', 'year' => '2024'],
                ],
                [
                    'developmentOnly' => true,
                    'start_development' => '2024-03-15',
                    'end_development' => '2024-06-30',
                ]
            ],
            'RDEL - Not development only with all milestones - valid' => [
                true,
                [
                    'developmentOnly' => 'false',
                    'start_development' => ['day' => '15', 'month' => '01', 'year' => '2024'],
                    'end_development' => ['day' => '30', 'month' => '03', 'year' => '2024'],
                    'nonDevelopmentalMilestones' => [
                        'start_delivery' => ['day' => '01', 'month' => '04', 'year' => '2024'],
                        'end_delivery' => ['day' => '31', 'month' => '12', 'year' => '2025'],
                    ]
                ],
                [
                    'developmentOnly' => false,
                    'start_development' => '2024-01-15',
                    'end_development' => '2024-03-30',
                    'start_delivery' => '2024-04-01',
                    'end_delivery' => '2025-12-31',
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataCDELForm
     */
    public function testCDELForm(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([MilestoneDatesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsSchemeReturn $cdelSchemeReturn */
        $cdelSchemeReturn = $this->referenceRepository->getReference(
            MilestoneDatesTypeFixtures::CRSTS_SCHEME_RETURN_CDEL,
            CrstsSchemeReturn::class
        );

        $form = $this->formFactory->create(MilestoneDatesType::class, $cdelSchemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);

        // Verify CDEL form has correct fields
        $this->assertTrue($form->has('developmentOnly'));
        $this->assertTrue($form->has('start_development'));
        $this->assertTrue($form->has('end_development'));
        $this->assertTrue($form->has('nonDevelopmentalMilestones'));

        $nonDevForm = $form->get('nonDevelopmentalMilestones');
        $this->assertTrue($nonDevForm->has('start_construction'));
        $this->assertTrue($nonDevForm->has('end_construction'));
        $this->assertTrue($nonDevForm->has('final_delivery'));

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
     * @dataProvider dataRDELForm
     */
    public function testRDELForm(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([MilestoneDatesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsSchemeReturn $rdelSchemeReturn */
        $rdelSchemeReturn = $this->referenceRepository->getReference(
            MilestoneDatesTypeFixtures::CRSTS_SCHEME_RETURN_RDEL,
            CrstsSchemeReturn::class
        );

        $form = $this->formFactory->create(MilestoneDatesType::class, $rdelSchemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);

        // Verify RDEL form has correct fields
        $this->assertTrue($form->has('developmentOnly'));
        $this->assertTrue($form->has('start_development'));
        $this->assertTrue($form->has('end_development'));
        $this->assertTrue($form->has('nonDevelopmentalMilestones'));

        $nonDevForm = $form->get('nonDevelopmentalMilestones');
        $this->assertTrue($nonDevForm->has('start_delivery'));
        $this->assertTrue($nonDevForm->has('end_delivery'));
        // RDEL should NOT have construction or final_delivery fields
        $this->assertFalse($nonDevForm->has('start_construction'));
        $this->assertFalse($nonDevForm->has('end_construction'));
        $this->assertFalse($nonDevForm->has('final_delivery'));

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
     * @dataProvider dataCDELForm
     */
    public function testCDELDataMapper(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        if (!$expectedToBeValid || empty($expectedData)) {
            $this->assertTrue(true, 'Skipping data mapper test for invalid form');
            return;
        }

        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([MilestoneDatesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsSchemeReturn $cdelSchemeReturn */
        $cdelSchemeReturn = $this->referenceRepository->getReference(
            MilestoneDatesTypeFixtures::CRSTS_SCHEME_RETURN_CDEL,
            CrstsSchemeReturn::class
        );

        $form = $this->formFactory->create(MilestoneDatesType::class, $cdelSchemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);

        $form->submit($formData);

        $this->assertTrue($form->isValid(), 'Form should be valid for data mapper test');

        // Test that the data was correctly mapped back to the entity
        $updatedEntity = $form->getData();
        $this->assertInstanceOf(CrstsSchemeReturn::class, $updatedEntity);

        // Test developmentOnly mapping
        $this->assertEquals(
            $expectedData['developmentOnly'],
            $updatedEntity->getDevelopmentOnly(),
            'developmentOnly should be correctly mapped'
        );

        // Test milestone mappings
        foreach ($expectedData as $milestoneKey => $expectedDate) {
            if ($milestoneKey === 'developmentOnly') {
                continue;
            }

            $milestoneType = MilestoneType::from($milestoneKey);
            $milestone = $updatedEntity->getMilestoneByType($milestoneType);

            if ($expectedData['developmentOnly'] && !$milestoneType->isDevelopmentMilestone()) {
                // Should be removed for development-only schemes
                $this->assertNull($milestone, "Non-development milestone {$milestoneKey} should be removed for development-only scheme");
            } else {
                $this->assertNotNull($milestone, "Milestone {$milestoneKey} should exist");
                $this->assertEquals(
                    $expectedDate,
                    $milestone->getDate()->format('Y-m-d'),
                    "Milestone {$milestoneKey} date should be correctly mapped"
                );
            }
        }
    }

    /**
     * @dataProvider dataRDELForm
     */
    public function testRDELDataMapper(bool $expectedToBeValid, array $formData, array $expectedData = []): void
    {
        if (!$expectedToBeValid || empty($expectedData)) {
            $this->assertTrue(true, 'Skipping data mapper test for invalid form');
            return;
        }

        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([MilestoneDatesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsSchemeReturn $rdelSchemeReturn */
        $rdelSchemeReturn = $this->referenceRepository->getReference(
            MilestoneDatesTypeFixtures::CRSTS_SCHEME_RETURN_RDEL,
            CrstsSchemeReturn::class
        );

        $form = $this->formFactory->create(MilestoneDatesType::class, $rdelSchemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);

        $form->submit($formData);

        $this->assertTrue($form->isValid(), 'Form should be valid for data mapper test');

        // Test that the data was correctly mapped back to the entity
        $updatedEntity = $form->getData();
        $this->assertInstanceOf(CrstsSchemeReturn::class, $updatedEntity);

        // Test developmentOnly mapping
        $this->assertEquals(
            $expectedData['developmentOnly'],
            $updatedEntity->getDevelopmentOnly(),
            'developmentOnly should be correctly mapped'
        );

        // Test milestone mappings
        foreach ($expectedData as $milestoneKey => $expectedDate) {
            if ($milestoneKey === 'developmentOnly') {
                continue;
            }

            $milestoneType = MilestoneType::from($milestoneKey);
            $milestone = $updatedEntity->getMilestoneByType($milestoneType);

            if ($expectedData['developmentOnly'] && !$milestoneType->isDevelopmentMilestone()) {
                // Should be removed for development-only schemes
                $this->assertNull($milestone, "Non-development milestone {$milestoneKey} should be removed for development-only scheme");
            } else {
                $this->assertNotNull($milestone, "Milestone {$milestoneKey} should exist");
                $this->assertEquals(
                    $expectedDate,
                    $milestone->getDate()->format('Y-m-d'),
                    "Milestone {$milestoneKey} date should be correctly mapped"
                );
            }
        }
    }

    public function testDynamicFormGeneration(): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([MilestoneDatesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsSchemeReturn $cdelSchemeReturn */
        $cdelSchemeReturn = $this->referenceRepository->getReference(
            MilestoneDatesTypeFixtures::CRSTS_SCHEME_RETURN_CDEL,
            CrstsSchemeReturn::class
        );

        /** @var CrstsSchemeReturn $rdelSchemeReturn */
        $rdelSchemeReturn = $this->referenceRepository->getReference(
            MilestoneDatesTypeFixtures::CRSTS_SCHEME_RETURN_RDEL,
            CrstsSchemeReturn::class
        );

        // Test CDEL form generation
        $cdelForm = $this->formFactory->create(MilestoneDatesType::class, $cdelSchemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);

        $this->assertEquals(FundedMostlyAs::CDEL, $cdelSchemeReturn->getScheme()->getCrstsData()->getFundedMostlyAs());

        // CDEL should have construction and final delivery fields
        $nonDevForm = $cdelForm->get('nonDevelopmentalMilestones');
        $this->assertTrue($nonDevForm->has('start_construction'));
        $this->assertTrue($nonDevForm->has('end_construction'));
        $this->assertTrue($nonDevForm->has('final_delivery'));

        // Test RDEL form generation
        $rdelForm = $this->formFactory->create(MilestoneDatesType::class, $rdelSchemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);

        $this->assertEquals(FundedMostlyAs::RDEL, $rdelSchemeReturn->getScheme()->getCrstsData()->getFundedMostlyAs());

        // RDEL should have delivery fields instead
        $nonDevForm = $rdelForm->get('nonDevelopmentalMilestones');
        $this->assertTrue($nonDevForm->has('start_delivery'));
        $this->assertTrue($nonDevForm->has('end_delivery'));
        $this->assertFalse($nonDevForm->has('start_construction'));
        $this->assertFalse($nonDevForm->has('end_construction'));
        $this->assertFalse($nonDevForm->has('final_delivery'));
    }

    public function testExistingMilestonesPrePopulation(): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([MilestoneDatesTypeFixtures::class])
            ->getReferenceRepository();

        /** @var CrstsSchemeReturn $cdelSchemeReturn */
        $cdelSchemeReturn = $this->referenceRepository->getReference(
            MilestoneDatesTypeFixtures::CRSTS_SCHEME_RETURN_CDEL,
            CrstsSchemeReturn::class
        );

        $form = $this->formFactory->create(MilestoneDatesType::class, $cdelSchemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);

        // Test that existing milestones are pre-populated
        $this->assertEquals(false, $form->get('developmentOnly')->getData());

        // Check that existing milestone dates are populated
        $startDevData = $form->get('start_development')->getData();
        $this->assertInstanceOf(\DateTimeInterface::class, $startDevData);
        $this->assertEquals('2024-01-01', $startDevData->format('Y-m-d'));

        $endDevData = $form->get('end_development')->getData();
        $this->assertInstanceOf(\DateTimeInterface::class, $endDevData);
        $this->assertEquals('2024-06-01', $endDevData->format('Y-m-d'));
    }
}