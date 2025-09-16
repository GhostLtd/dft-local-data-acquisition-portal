<?php

namespace App\Tests\Form\Type\Admin;

use App\Entity\Authority;
use App\Entity\User;
use App\Form\Type\Admin\AuthorityType;
use App\Tests\DataFixtures\Form\Type\Admin\AuthorityTypeFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class AuthorityTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid new authority with admin' => [
                true,
                [
                    'name' => 'Test Authority',
                    'admin' => [
                        'name' => 'John Doe',
                        'email' => 'john.doe@example.com',
                        'position' => 'Manager',
                        'phone' => '01234567890'
                    ],
                    'funds' => ['CRSTS1']
                ]
            ],
            'Empty authority name - invalid' => [
                false,
                [
                    'name' => '',
                    'admin' => [
                        'name' => 'John Doe',
                        'email' => 'john.doe@example.com',
                        'position' => 'Manager',
                        'phone' => '01234567890'
                    ],
                    'funds' => ['CRSTS1']
                ]
            ],
            'Admin with empty name - invalid' => [
                false,
                [
                    'name' => 'Test Authority',
                    'admin' => [
                        'name' => '',
                        'email' => 'john.doe@example.com',
                        'position' => 'Manager',
                        'phone' => '01234567890'
                    ],
                    'funds' => ['CRSTS1']
                ]
            ],
            'Admin with empty email - invalid' => [
                false,
                [
                    'name' => 'Test Authority',
                    'admin' => [
                        'name' => 'John Doe',
                        'email' => '',
                        'position' => 'Manager',
                        'phone' => '01234567890'
                    ],
                    'funds' => ['CRSTS1']
                ]
            ],
            'Admin with invalid email format - invalid' => [
                false,
                [
                    'name' => 'Test Authority',
                    'admin' => [
                        'name' => 'John Doe',
                        'email' => 'invalid-email',
                        'position' => 'Manager',
                        'phone' => '01234567890'
                    ],
                    'funds' => ['CRSTS1']
                ]
            ],
            'Admin with empty position - invalid' => [
                false,
                [
                    'name' => 'Test Authority',
                    'admin' => [
                        'name' => 'John Doe',
                        'email' => 'john.doe@example.com',
                        'position' => '',
                        'phone' => '01234567890'
                    ],
                    'funds' => ['CRSTS1']
                ]
            ],
            'Admin with empty phone - invalid' => [
                false,
                [
                    'name' => 'Test Authority',
                    'admin' => [
                        'name' => 'John Doe',
                        'email' => 'john.doe@example.com',
                        'position' => 'Manager',
                        'phone' => ''
                    ],
                    'funds' => ['CRSTS1']
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $authority = new Authority(); // New authority (no ID)

        $form = $this->formFactory->create(AuthorityType::class, $authority, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);


        $this->assertEquals($expectedToBeValid, $form->isValid());
    }

    public function dataFormExistingAuthority(): array
    {
        return [
            'Valid existing authority - choose existing admin' => [
                true,
                [
                    'name' => 'Updated Authority Name',
                    'admin_choice' => 'existing',
                    'existing_admin' => null // Will be set to actual User in test
                ]
            ],
            'Valid existing authority - choose new admin' => [
                true,
                [
                    'name' => 'Updated Authority Name',
                    'admin_choice' => 'new',
                    'admin' => [
                        'name' => 'Alice Johnson',
                        'email' => 'alice.johnson@example.com',
                        'position' => 'Director',
                        'phone' => '09876543210'
                    ]
                ]
            ],
            'Invalid existing admin - invalid option' => [
                false,
                [
                    'name' => '',
                    'admin_choice' => 'existing',
                    'existing_admin' => 'banana',
                ]
            ],
            'Invalid existing admin - empty name' => [
                false,
                [
                    'name' => '',
                    'admin_choice' => 'existing',
                    'existing_admin' => null
                ]
            ],
            'Invalid new admin - empty email' => [
                false,
                [
                    'name' => 'Updated Authority Name',
                    'admin_choice' => 'new',
                    'admin' => [
                        'name' => 'Alice Johnson',
                        'email' => '',
                        'position' => 'Director',
                        'phone' => '09876543210'
                    ]
                ]
            ],
            'Invalid new admin - bad email format' => [
                false,
                [
                    'name' => 'Updated Authority Name',
                    'admin_choice' => 'new',
                    'admin' => [
                        'name' => 'Alice Johnson',
                        'email' => 'invalid-email',
                        'position' => 'Director',
                        'phone' => '09876543210'
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataFormExistingAuthority
     */
    public function testFormExistingAuthority(bool $expectedToBeValid, array $formData): void
    {
        // Load fixtures just for this test
        $referenceRepository = $this->databaseTool
            ->loadFixtures([AuthorityTypeFixtures::class])
            ->getReferenceRepository();

        /** @var Authority $authority */
        $authority = $referenceRepository->getReference('authority', Authority::class);

        /** @var User $user1 */
        $user1 = $referenceRepository->getReference('user-1', User::class);

        // Set the existing_admin to the fixture user ID if needed
        if (array_key_exists('existing_admin', $formData) && $formData['existing_admin'] === null) {
            $formData['existing_admin'] = $user1->getId()->__toString();
        }

        $form = $this->formFactory->create(AuthorityType::class, $authority, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);


        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}