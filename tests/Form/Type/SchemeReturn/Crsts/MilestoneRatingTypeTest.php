<?php

namespace App\Tests\Form\Type\SchemeReturn\Crsts;

use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\SchemeReturn\Crsts\MilestoneRatingType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class MilestoneRatingTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid complete data' => [
                true,
                [
                    'onTrackRating' => 'green',
                    'progressUpdate' => 'Milestone is progressing as planned with all deliverables on schedule.',
                    'risks' => 'No significant risks identified at this time.'
                ]
            ],
            'Empty on track rating - invalid' => [
                false,
                [
                    'onTrackRating' => '',
                    'progressUpdate' => 'Milestone is progressing as planned with all deliverables on schedule.',
                    'risks' => 'No significant risks identified at this time.'
                ]
            ],
            'Invalid on track rating - invalid' => [
                false,
                [
                    'onTrackRating' => 'invalid_rating',
                    'progressUpdate' => 'Milestone is progressing as planned with all deliverables on schedule.',
                    'risks' => 'No significant risks identified at this time.'
                ]
            ],
            'Empty progress update - invalid' => [
                false,
                [
                    'onTrackRating' => 'green',
                    'progressUpdate' => '',
                    'risks' => 'No significant risks identified at this time.'
                ]
            ],
            'Progress update too long - invalid' => [
                false,
                [
                    'onTrackRating' => 'green',
                    'progressUpdate' => str_repeat('A', 16384), // Max is 16383 based on Length constraint
                    'risks' => 'No significant risks identified at this time.'
                ]
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $schemeReturn = new CrstsSchemeReturn();

        $form = $this->formFactory->create(MilestoneRatingType::class, $schemeReturn, [
            'csrf_protection' => false,
            'cancel_url' => '/test-cancel'
        ]);
        $form->submit($formData);


        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}
