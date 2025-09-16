<?php

namespace App\Tests\Form\Type;

use App\Form\Type\BaseUserType;

class BaseUserTypeTest extends AbstractKernelTypeTest
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
            'Valid empty data' => [
                true,
                [
                    'name' => '',
                    'email' => '',
                    'position' => '',
                    'phone' => ''
                ]
            ],
            'Invalid data (still acceptable) - other forms which include this form will handle the validation' => [
                true,
                [
                    'name' => 'John Doe',
                    'email' => 'invalid-email',
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
        $form = $this->formFactory->create(BaseUserType::class, null, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}