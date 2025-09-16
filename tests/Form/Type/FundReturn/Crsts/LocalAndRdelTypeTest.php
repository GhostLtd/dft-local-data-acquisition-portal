<?php

namespace App\Tests\Form\Type\FundReturn\Crsts;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Form\Type\FundReturn\Crsts\LocalAndRdelType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class LocalAndRdelTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid complete data' => [
                true,
                [
                    'localContribution' => 'Local authority will contribute £500k from transport budget.',
                    'resourceFunding' => 'RDEL funding of £200k allocated for project management and admin costs.'
                ]
            ],
            'Empty local contribution - invalid' => [
                false,
                [
                    'localContribution' => '',
                    'resourceFunding' => 'RDEL funding of £200k allocated for project management and admin costs.'
                ]
            ],
            'Empty resource funding - invalid' => [
                false,
                [
                    'localContribution' => 'Local authority will contribute £500k from transport budget.',
                    'resourceFunding' => ''
                ]
            ],
            'Both fields empty - invalid' => [
                false,
                [
                    'localContribution' => '',
                    'resourceFunding' => ''
                ]
            ],
            'Local contribution too long - invalid' => [
                false,
                [
                    'localContribution' => str_repeat('A', 16384), // Max is 16383
                    'resourceFunding' => 'RDEL funding of £200k allocated for project management and admin costs.'
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $fundReturn = new CrstsFundReturn();

        $form = $this->formFactory->create(LocalAndRdelType::class, $fundReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}
