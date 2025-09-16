<?php

namespace App\Tests\Form\Type;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\Enum\TransportModeCategory;
use App\Entity\Scheme;
use App\Form\Type\SchemeType;
use App\Tests\DataFixtures\Form\Type\SchemeTypeEditModeFixtures;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SchemeTypeEditModeTest extends AbstractKernelTypeTest
{
    private ReferenceRepository $referenceRepository;

    public function dataForm(): array
    {
        return [
            // Basic validation tests for existing schemes (MODE_EDIT)
            'Empty name - invalid' => [
                false,
                [
                    'description' => 'Test scheme description',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false'
                ]
            ],
            'Valid bus scheme - valid' => [
                true,
                [
                    'name' => 'Updated Bus Scheme',
                    'description' => 'Updated description',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false'
                ],
                [
                    'name' => 'Updated Bus Scheme',
                    'description' => 'Updated description',
                    'transportModeCategory' => TransportModeCategory::BUS
                ]
            ],

            // CRSTS fund validation tests for EDIT mode
            'CRSTS fund without retained field - invalid' => [
                false,
                [
                    'name' => 'CRSTS Scheme Incomplete',
                    'description' => 'Test CRSTS scheme without retained field',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false',
                    'funds' => ['CRSTS1']
                ]
            ],
            'CRSTS fund without previouslyTcf field - invalid' => [
                false,
                [
                    'name' => 'CRSTS Scheme Incomplete',
                    'description' => 'Test CRSTS scheme without previouslyTcf field',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false',
                    'funds' => ['CRSTS1'],
                    'crstsData' => [
                        'retained' => 'true'
                        // previouslyTcf is missing
                    ]
                ]
            ],
            'CRSTS fund with complete fields - valid' => [
                true,
                [
                    'name' => 'CRSTS Scheme Complete',
                    'description' => 'Test CRSTS scheme with all required fields',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false',
                    'funds' => ['CRSTS1'],
                    'crstsData' => [
                        'retained' => 'true',
                        'previouslyTcf' => 'false'
                    ]
                ],
                [
                    'name' => 'CRSTS Scheme Complete',
                    'description' => 'Test CRSTS scheme with all required fields',
                    'transportModeCategory' => TransportModeCategory::BUS
                ]
            ],

            // Transport mode validation tests
            'Bus without sub-option - invalid' => [
                false,
                [
                    'name' => 'Bus Scheme',
                    'description' => 'Test bus scheme',
                    'transportModeCategory' => 'bus',
                    'hasActiveTravelElements' => 'false'
                ]
            ],
            'Bus with sub-option - valid' => [
                true,
                [
                    'name' => 'Bus Scheme',
                    'description' => 'Test bus scheme',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false'
                ],
                [
                    'name' => 'Bus Scheme',
                    'description' => 'Test bus scheme',
                    'transportModeCategory' => TransportModeCategory::BUS
                ]
            ],

            // Rail tests
            'Rail with sub-option - valid' => [
                true,
                [
                    'name' => 'Rail Scheme',
                    'description' => 'Test rail scheme',
                    'transportModeCategory' => 'rail',
                    'transportModeRail' => 'rail.interchange_or_network_upgrade',
                    'hasActiveTravelElements' => 'false'
                ],
                [
                    'name' => 'Rail Scheme',
                    'description' => 'Test rail scheme',
                    'transportModeCategory' => TransportModeCategory::RAIL
                ]
            ],
            'Rail without sub-option - invalid' => [
                false,
                [
                    'name' => 'Rail Scheme',
                    'description' => 'Test rail scheme',
                    'transportModeCategory' => 'rail',
                    'hasActiveTravelElements' => 'false'
                ]
            ],

            // Tram tests
            'Tram with sub-option - valid' => [
                true,
                [
                    'name' => 'Tram Scheme',
                    'description' => 'Test tram scheme',
                    'transportModeCategory' => 'tram',
                    'transportModeTram' => 'tram.interchange_or_network_upgrade',
                    'hasActiveTravelElements' => 'false'
                ],
                [
                    'name' => 'Tram Scheme',
                    'description' => 'Test tram scheme',
                    'transportModeCategory' => TransportModeCategory::TRAM
                ]
            ],
            'Tram without sub-option - invalid' => [
                false,
                [
                    'name' => 'Tram Scheme',
                    'description' => 'Test tram scheme',
                    'transportModeCategory' => 'tram',
                    'hasActiveTravelElements' => 'false'
                ]
            ],

            // Road tests
            'Road with sub-option - valid' => [
                true,
                [
                    'name' => 'Road Scheme',
                    'description' => 'Test road scheme',
                    'transportModeCategory' => 'road',
                    'transportModeRoad' => 'road.highways_maintenance',
                    'hasActiveTravelElements' => 'false'
                ],
                [
                    'name' => 'Road Scheme',
                    'description' => 'Test road scheme',
                    'transportModeCategory' => TransportModeCategory::ROAD
                ]
            ],
            'Road without sub-option - invalid' => [
                false,
                [
                    'name' => 'Road Scheme',
                    'description' => 'Test road scheme',
                    'transportModeCategory' => 'road',
                    'hasActiveTravelElements' => 'false'
                ]
            ],

            // Other tests
            'Other with sub-option - valid' => [
                true,
                [
                    'name' => 'Other Scheme',
                    'description' => 'Test other scheme',
                    'transportModeCategory' => 'other',
                    'transportModeOther' => 'other.staffing_and_resourcing',
                    'hasActiveTravelElements' => 'false'
                ],
                [
                    'name' => 'Other Scheme',
                    'description' => 'Test other scheme',
                    'transportModeCategory' => TransportModeCategory::OTHER
                ]
            ],
            'Other without sub-option - invalid' => [
                false,
                [
                    'name' => 'Other Scheme',
                    'description' => 'Test other scheme',
                    'transportModeCategory' => 'other',
                    'hasActiveTravelElements' => 'false'
                ]
            ],

            // Active travel tests (no hasActiveTravelElements field)
            'Active travel with sub-option - valid' => [
                true,
                [
                    'name' => 'Active Travel Scheme',
                    'description' => 'Test active travel scheme',
                    'transportModeCategory' => 'active_travel',
                    'transportModeActive_travel' => 'active_travel.new_segregated_cycling_facility'
                ],
                [
                    'name' => 'Active Travel Scheme',
                    'description' => 'Test active travel scheme',
                    'transportModeCategory' => TransportModeCategory::ACTIVE_TRAVEL
                ]
            ],
            'Active travel without sub-option - invalid' => [
                false,
                [
                    'name' => 'Active Travel Scheme',
                    'description' => 'Test active travel scheme',
                    'transportModeCategory' => 'active_travel'
                ]
            ],

            // Multi-modal tests
            'Multi-modal with sub-option - valid' => [
                true,
                [
                    'name' => 'Multi Modal Scheme',
                    'description' => 'Test multi-modal scheme',
                    'transportModeCategory' => 'multi_modal',
                    'transportModeMulti_modal' => 'multi_modal.interchange',
                    'hasActiveTravelElements' => 'false'
                ],
                [
                    'name' => 'Multi Modal Scheme',
                    'description' => 'Test multi-modal scheme',
                    'transportModeCategory' => TransportModeCategory::MULTI_MODAL
                ]
            ],
            'Multi-modal without sub-option - invalid' => [
                false,
                [
                    'name' => 'Multi Modal Scheme',
                    'description' => 'Test multi-modal scheme',
                    'transportModeCategory' => 'multi_modal',
                    'hasActiveTravelElements' => 'false'
                ]
            ],

            // Active travel elements for non-active-travel modes
            'Bus with active travel elements but no sub-field - invalid' => [
                false,
                [
                    'name' => 'Bus Scheme',
                    'description' => 'Test bus scheme',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'true'
                ]
            ],
            'Bus with active travel elements and sub-field - valid' => [
                true,
                [
                    'name' => 'Bus Scheme',
                    'description' => 'Test bus scheme',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'true',
                    'activeTravelElement' => 'new_segregated_cycling'
                ],
                [
                    'name' => 'Bus Scheme',
                    'description' => 'Test bus scheme',
                    'transportModeCategory' => TransportModeCategory::BUS
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([SchemeTypeEditModeFixtures::class])
            ->getReferenceRepository();

        /** @var Scheme $scheme */
        $scheme = $this->referenceRepository->getReference(SchemeTypeEditModeFixtures::EXISTING_SCHEME, Scheme::class);
        /** @var Authority $authority */
        $authority = $this->referenceRepository->getReference(SchemeTypeEditModeFixtures::AUTHORITY, Authority::class);

        // Verify the scheme has an ID (for EDIT mode)
        $this->assertNotNull($scheme->getId(), 'Scheme should have an ID for EDIT mode testing');

        // Mock authorization checker to grant all permissions
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        // Replace the authorization checker service
        $this->getContainer()->set('security.authorization_checker', $authChecker);

        $form = $this->formFactory->create(SchemeType::class, $scheme, [
            'csrf_protection' => false,
            'authority' => $authority,
            'cancel_url' => '/test-cancel'
        ]);

        // Verify checklist field is NOT present in EDIT mode
        $this->assertFalse($form->has('checklist'), 'Checklist field should not be present in EDIT mode');

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
            ->loadFixtures([SchemeTypeEditModeFixtures::class])
            ->getReferenceRepository();

        /** @var Scheme $scheme */
        $scheme = $this->referenceRepository->getReference(SchemeTypeEditModeFixtures::EXISTING_SCHEME, Scheme::class);
        /** @var Authority $authority */
        $authority = $this->referenceRepository->getReference(SchemeTypeEditModeFixtures::AUTHORITY, Authority::class);

        // Mock authorization checker to grant all permissions
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        // Replace the authorization checker service
        $this->getContainer()->set('security.authorization_checker', $authChecker);

        $form = $this->formFactory->create(SchemeType::class, $scheme, [
            'csrf_protection' => false,
            'authority' => $authority,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);

        $this->assertTrue($form->isValid(), 'Form should be valid for data mapper test');

        $updatedEntity = $form->getData();
        $this->assertInstanceOf(Scheme::class, $updatedEntity);

        $this->assertEquals(
            $expectedData['name'],
            $updatedEntity->getName(),
            'Name should be correctly mapped'
        );

        $this->assertEquals(
            $expectedData['description'],
            $updatedEntity->getDescription(),
            'Description should be correctly mapped'
        );

        $this->assertEquals(
            $expectedData['transportModeCategory'],
            $updatedEntity->getTransportMode()->category(),
            'Transport mode category should be correctly mapped'
        );
    }

    public function testFormWithDeniedPermissions(): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([SchemeTypeEditModeFixtures::class])
            ->getReferenceRepository();

        /** @var Scheme $scheme */
        $scheme = $this->referenceRepository->getReference(SchemeTypeEditModeFixtures::EXISTING_SCHEME, Scheme::class);
        /** @var Authority $authority */
        $authority = $this->referenceRepository->getReference(SchemeTypeEditModeFixtures::AUTHORITY, Authority::class);

        // Mock authorization checker to deny CRSTS field editing
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturnMap([
            [Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS, $scheme, true],
            [Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS, $scheme, false], // Denied
            [Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME, $scheme, true]
        ]);

        // Replace the authorization checker service
        $this->getContainer()->set('security.authorization_checker', $authChecker);

        $form = $this->formFactory->create(SchemeType::class, $scheme, [
            'csrf_protection' => false,
            'authority' => $authority,
            'cancel_url' => '/test-cancel'
        ]);

        // Check that retained field is disabled
        $retainedField = $form->get('crstsData')->get('retained');
        $this->assertTrue(
            $retainedField->getConfig()->getDisabled(),
            'Retained field should be disabled when CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS is denied'
        );
    }

    public function testFormWithDeniedFundRemovalPermission(): void
    {
        $this->referenceRepository = $this->databaseTool
            ->loadFixtures([SchemeTypeEditModeFixtures::class])
            ->getReferenceRepository();

        /** @var Scheme $scheme */
        $scheme = $this->referenceRepository->getReference(SchemeTypeEditModeFixtures::EXISTING_SCHEME, Scheme::class);
        /** @var Authority $authority */
        $authority = $this->referenceRepository->getReference(SchemeTypeEditModeFixtures::AUTHORITY, Authority::class);

        // Mock authorization checker to deny CRSTS fund removal
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturnMap([
            [Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS, $scheme, true],
            [Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS, $scheme, true],
            [Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME, $scheme, false] // Denied
        ]);

        // Replace the authorization checker service
        $this->getContainer()->set('security.authorization_checker', $authChecker);

        $form = $this->formFactory->create(SchemeType::class, $scheme, [
            'csrf_protection' => false,
            'authority' => $authority,
            'cancel_url' => '/test-cancel'
        ]);

        // Check the funds field configuration
        $fundsField = $form->get('funds');
        $choiceOptionsCallable = $fundsField->getConfig()->getOption('choice_options');

        // Test the choice_options callable with CRSTS1 fund
        $crsts1Fund = \App\Entity\Enum\Fund::CRSTS1;
        $crstsChoiceOptions = $choiceOptionsCallable($crsts1Fund);

        $this->assertIsArray($crstsChoiceOptions, 'CRSTS1 choice options should be an array');
        $this->assertTrue(
            $crstsChoiceOptions['disabled'] ?? false,
            'CRSTS1 choice should be disabled when CAN_REMOVE_CRSTS_FUND_FROM_SCHEME is denied'
        );
    }
}