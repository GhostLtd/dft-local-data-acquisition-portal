<?php

namespace App\Tests\Form\Type\FundReturn\Crsts;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Form\Type\FundReturn\Crsts\OverallProgressType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class OverallProgressTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid complete data' => [
                true,
                [
                    'progressSummary' => 'Project is progressing well with milestones on track.',
                    'deliveryConfidence' => 'High confidence in delivery based on current progress.',
                    'overallConfidence' => 'green'
                ]
            ],
            'Empty progress summary - invalid' => [
                false,
                [
                    'progressSummary' => '',
                    'deliveryConfidence' => 'High confidence in delivery based on current progress.',
                    'overallConfidence' => 'green'
                ]
            ],
            'Empty delivery confidence - invalid' => [
                false,
                [
                    'progressSummary' => 'Project is progressing well with milestones on track.',
                    'deliveryConfidence' => '',
                    'overallConfidence' => 'green'
                ]
            ],
            'Invalid overall confidence rating - invalid' => [
                false,
                [
                    'progressSummary' => 'Project is progressing well with milestones on track.',
                    'deliveryConfidence' => 'High confidence in delivery based on current progress.',
                    'overallConfidence' => 'INVALID_RATING'
                ]
            ],
            'Missing overall confidence - invalid' => [
                false,
                [
                    'progressSummary' => 'Project is progressing well with milestones on track.',
                    'deliveryConfidence' => 'High confidence in delivery based on current progress.',
                    'overallConfidence' => ''
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

        $form = $this->formFactory->create(OverallProgressType::class, $fundReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}
