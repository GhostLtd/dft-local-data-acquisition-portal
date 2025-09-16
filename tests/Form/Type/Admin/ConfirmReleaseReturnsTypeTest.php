<?php

namespace App\Tests\Form\Type\Admin;

use App\Form\Type\Admin\ConfirmReleaseReturnsType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class ConfirmReleaseReturnsTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Checkbox checked - valid' => [
                true,
                ['confirm' => '1']
            ],
            'Checkbox unchecked - invalid' => [
                false,
                []
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $form = $this->formFactory->create(ConfirmReleaseReturnsType::class, null, [
            'csrf_protection' => false,
            'cancel_link_options' => ['href' => '/test-cancel']
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}