<?php

namespace App\Tests\Form\Type\Admin;

use App\Entity\Authority;
use App\Form\Type\Admin\FundAwardType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class FundAwardTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid fund selection' => [
                true,
                [
                    'funds' => ['CRSTS1']
                ]
            ],
            'Empty fund selection - valid (removes all funds)' => [
                true,
                [
                    'funds' => []
                ]
            ],
            'Invalid fund choice - invalid' => [
                false,
                [
                    'funds' => ['INVALID_FUND']
                ]
            ],
            'Multiple valid funds - valid' => [
                true,
                [
                    'funds' => ['CRSTS1'] // Only CRSTS1 is enabled according to Fund::enabledCases()
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
        $authority->setName('Test Authority');

        $form = $this->formFactory->create(FundAwardType::class, $authority, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}