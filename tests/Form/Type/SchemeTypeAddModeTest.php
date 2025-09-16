<?php

namespace App\Tests\Form\Type;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\Enum\TransportModeCategory;
use App\Entity\Scheme;
use App\Form\Type\SchemeType;
use App\Tests\DataFixtures\Form\Type\SchemeTypeAddModeFixtures;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SchemeTypeAddModeTest extends AbstractKernelTypeTest
{
    private ReferenceRepository $referenceRepository;

    public function dataForm(): array
    {
        return [
            // Basic validation tests for new schemes (MODE_ADD)
            'Empty name - invalid' => [
                false,
                [
                    'description' => 'Test scheme description',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false',
                    'checklist' => [
                        'dft_approved' => '1'
                    ]
                ]
            ],
            'Missing checklist - invalid' => [
                false,
                [
                    'name' => 'Test Scheme Name',
                    'description' => 'Test scheme description',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false'
                ]
            ],
            'Checklist not approved - invalid' => [
                false,
                [
                    'name' => 'Test Scheme Name',
                    'description' => 'Test scheme description',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false',
                    'checklist' => []
                ]
            ],

            // CRSTS fund validation tests for ADD mode
            'CRSTS fund without retained field - invalid' => [
                false,
                [
                    'name' => 'CRSTS Scheme Incomplete',
                    'description' => 'Test CRSTS scheme without retained field',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false',
                    'funds' => ['CRSTS1'],
                    'checklist' => [
                        'dft_approved' => '1'
                    ]
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
                    ],
                    'checklist' => [
                        'dft_approved' => '1'
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
                    ],
                    'checklist' => [
                        'dft_approved' => '1'
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
                    'hasActiveTravelElements' => 'false',
                    'checklist' => [
                        'dft_approved' => '1'
                    ]
                ]
            ],
            'Bus with sub-option - valid' => [
                true,
                [
                    'name' => 'Bus Scheme',
                    'description' => 'Test bus scheme',
                    'transportModeCategory' => 'bus',
                    'transportModeBus' => 'bus.bus_priority_measures',
                    'hasActiveTravelElements' => 'false',
                    'checklist' => [
                        'dft_approved' => '1'
                    ]
                ],
                [
                    'name' => 'Bus Scheme',
                    'description' => 'Test bus scheme',
                    'transportModeCategory' => TransportModeCategory::BUS
                ]
            ],

            // Active travel tests (no hasActiveTravelElements field)
            'Active travel with sub-option - valid' => [
                true,
                [
                    'name' => 'Active Travel Scheme',
                    'description' => 'Test active travel scheme',
                    'transportModeCategory' => 'active_travel',
                    'transportModeActive_travel' => 'active_travel.new_segregated_cycling_facility',
                    'checklist' => [
                        'dft_approved' => '1'
                    ]
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
                    'transportModeCategory' => 'active_travel',
                    'checklist' => [
                        'dft_approved' => '1'
                    ]
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
                    'hasActiveTravelElements' => 'false',
                    'checklist' => [
                        'dft_approved' => '1'
                    ]
                ],
                [
                    'name' => 'Multi Modal Scheme',
                    'description' => 'Test multi-modal scheme',
                    'transportModeCategory' => TransportModeCategory::MULTI_MODAL
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
                    'hasActiveTravelElements' => 'true',
                    'checklist' => [
                        'dft_approved' => '1'
                    ]
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
                    'activeTravelElement' => 'new_segregated_cycling',
                    'checklist' => [
                        'dft_approved' => '1'
                    ]
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
            ->loadFixtures([SchemeTypeAddModeFixtures::class])
            ->getReferenceRepository();

        /** @var Authority $authority */
        $authority = $this->referenceRepository->getReference(SchemeTypeAddModeFixtures::AUTHORITY, Authority::class);

        // Create a new scheme without persisting it (no ID) for MODE_ADD testing
        $scheme = (new Scheme())
            ->setName('Test New Scheme')
            ->setAuthority($authority);

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
            ->loadFixtures([SchemeTypeAddModeFixtures::class])
            ->getReferenceRepository();

        /** @var Authority $authority */
        $authority = $this->referenceRepository->getReference(SchemeTypeAddModeFixtures::AUTHORITY, Authority::class);

        // Create a new scheme without persisting it (no ID) for MODE_ADD testing
        $scheme = (new Scheme())
            ->setName('Test New Scheme')
            ->setAuthority($authority);

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
            ->loadFixtures([SchemeTypeAddModeFixtures::class])
            ->getReferenceRepository();

        /** @var Authority $authority */
        $authority = $this->referenceRepository->getReference(SchemeTypeAddModeFixtures::AUTHORITY, Authority::class);

        // Create a new scheme without persisting it (no ID) for MODE_ADD testing
        $scheme = (new Scheme())
            ->setName('Test New Scheme')
            ->setAuthority($authority);

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
}