<?php

namespace App\Tests\Form\Type;

use App\Form\Type\BaseButtonsFormType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class BaseButtonsFormTypeTest extends AbstractKernelTypeTest
{

    public function testRequiredCancelUrl(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->formFactory->create(BaseButtonsFormType::class, null, [
            'csrf_protection' => false
        ]);
    }

    public function dataFormSubmission(): array
    {
        return [
            'Submit with save button' => [
                true,
                [
                    'buttons' => [
                        'save' => ''
                    ]
                ]
            ],
            'Submit empty form' => [
                true,
                []
            ],
            'Submit with arbitrary data' => [
                false,
                [
                    'some_field' => 'some_value'
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataFormSubmission
     */
    public function testFormSubmission(bool $expectedToBeValid, array $formData): void
    {
        $form = $this->formFactory->create(BaseButtonsFormType::class, null, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);

        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
        $this->assertTrue($form->isSubmitted());
    }
}
