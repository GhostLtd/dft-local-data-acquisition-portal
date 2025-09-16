<?php

namespace App\Tests\Form\Type\FundReturn\Crsts;

use App\Form\Type\FundReturn\Crsts\SignOffConfirmationType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class SignOffConfirmationTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Both checkboxes checked - valid' => [
                true,
                [
                    'confirm_fco_approved' => '1',
                    'confirm_cannot_be_undone' => '1'
                ]
            ],
            'Only first checkbox checked - invalid' => [
                false,
                [
                    'confirm_fco_approved' => '1'
                ]
            ],
            'Only second checkbox checked - invalid' => [
                false,
                [
                    'confirm_cannot_be_undone' => '1'
                ]
            ],
            'Neither checkbox checked - invalid' => [
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
        $form = $this->formFactory->create(SignOffConfirmationType::class, null, [
            'csrf_protection' => false,
            'cancel_link_options' => ['href' => '/test-cancel']
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}