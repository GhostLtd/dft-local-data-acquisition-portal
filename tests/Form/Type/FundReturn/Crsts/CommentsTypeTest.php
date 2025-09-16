<?php

namespace App\Tests\Form\Type\FundReturn\Crsts;

use App\Form\Type\FundReturn\Crsts\CommentsType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class CommentsTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        // This form is not fussy
        return [
            'Valid comments' => [
                true,
                ['comments' => 'These are some test comments']
            ],
            'Empty comments' => [
                true,
                ['comments' => '']
            ],
            'Multi-line comments' => [
                true,
                ['comments' => "Line 1\nLine 2\nLine 3"]
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $form = $this->formFactory->create(CommentsType::class, null, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}