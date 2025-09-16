<?php

namespace App\Tests\Form\Type;

use App\Entity\Authority;
use App\Entity\User;
use App\Form\Type\UserType;
use Symfony\Component\Uid\Ulid;

class UserTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid filled data' => [
                true,
                [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'position' => 'Manager',
                    'phone' => '01234567890'
                ]
            ],
            'Empty name - invalid' => [
                false,
                [
                    'name' => '',
                    'email' => 'john.doe@example.com',
                    'position' => 'Manager',
                    'phone' => '01234567890'
                ]
            ],
            'Empty email - invalid' => [
                false,
                [
                    'name' => 'John Doe',
                    'email' => '',
                    'position' => 'Manager',
                    'phone' => '01234567890'
                ]
            ],
            'Invalid email format - invalid' => [
                false,
                [
                    'name' => 'John Doe',
                    'email' => 'invalid-email',
                    'position' => 'Manager',
                    'phone' => '01234567890'
                ]
            ],
            'Empty position - invalid' => [
                false,
                [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'position' => '',
                    'phone' => '01234567890'
                ]
            ],
            'Empty phone - invalid' => [
                false,
                [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'position' => 'Manager',
                    'phone' => ''
                ]
            ],
            'Name too long - invalid' => [
                false,
                [
                    'name' => str_repeat('A', 256), // Max is 255
                    'email' => 'john.doe@example.com',
                    'position' => 'Manager',
                    'phone' => '01234567890'
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $authority = new Authority();
        $authority->setId(new Ulid());

        $user = new User();

        $form = $this->formFactory->create(UserType::class, $user, [
            'csrf_protection' => false,
            'authority' => $authority,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}