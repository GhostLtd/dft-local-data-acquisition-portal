<?php

namespace App\Tests\Form\Type;

use App\Form\Type\LoginType;

class LoginTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid email' => [
                true,
                ['email' => 'user@example.com']
            ],
            'Valid email with plus sign' => [
                true,
                ['email' => 'user+test@example.com']
            ],
            'Valid email with subdomain' => [
                true,
                ['email' => 'user@mail.example.com']
            ],
            'Empty email' => [
                false,
                ['email' => '']
            ],
            'Invalid email format - no @' => [
                false,
                ['email' => 'userexample.com']
            ],
            'Invalid email format - no domain' => [
                false,
                ['email' => 'user@']
            ],
            'Invalid email format - no local part' => [
                false,
                ['email' => '@example.com']
            ],
            'Invalid email format - spaces' => [
                false,
                ['email' => 'user @example.com']
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $form = $this->formFactory->create(LoginType::class, null, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}