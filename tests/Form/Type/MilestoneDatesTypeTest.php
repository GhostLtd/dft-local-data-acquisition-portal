<?php

namespace App\Tests\Form\Type;

use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\Milestone;
use App\Entity\Scheme;
use App\Entity\SchemeData\CrstsData;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\SchemeReturn\Crsts\MilestoneDatesType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class MilestoneDatesTypeTest extends AbstractTypeTest
{
    public function dataForm(): array
    {
        $initialMilestones = '{"baseline_start_construction":"2025-04-01","start_development":"2025-05-01"}';
        $fundedMostlyAs = FundedMostlyAs::CDEL;

        $validDate = ['year' => '2025', 'month' => '3', 'day' => '3'];
        $formData = [
            'developmentOnly' => 'true',
            'start_development' => $validDate,
            'end_development' => $validDate,
        ];

        $allBaselines = '"baseline_end_construction":"2025-04-01","baseline_end_delivery":"2025-04-01","baseline_end_development":"2025-04-01","baseline_final_delivery":"2025-04-01","baseline_start_construction":"2025-04-01","baseline_start_delivery":"2025-04-01","baseline_start_development":"2025-04-01"';
        $allMilestones = '{'.$allBaselines.',"end_construction":"2025-04-01","end_delivery":"2025-04-01","end_development":"2025-04-01","final_delivery":"2025-04-01","start_construction":"2025-04-01","start_delivery":"2025-04-01","start_development":"2025-04-01"}';

        return array_merge(
            $this->getCDELValidationTests($validDate),
            $this->getRDELValidationTests($validDate),
            [
                'CDEL: development-only (baselines, start/end development remain)' => [
                    $allMilestones,
                    FundedMostlyAs::CDEL,
                    [
                        'developmentOnly' => 'true',
                        'start_development' => ['year' => '2025', 'month' => '3', 'day' => '3'],
                        'end_development' => ['year' => '2025', 'month' => '5', 'day' => '1'],
                    ],
                    true,
                    '{'.$allBaselines.',"end_development":"2025-05-01","start_development":"2025-03-01"}',
                ],
            ],
            [
                'RDEL: development-only (baselines, start/end construction remain)' => [
                    $allMilestones,
                    FundedMostlyAs::CDEL,
                    [
                        'developmentOnly' => 'true',
                        'start_development' => ['year' => '2025', 'month' => '3', 'day' => '3'],
                        'end_development' => ['year' => '2025', 'month' => '5', 'day' => '1'],
                    ],
                    true,
                    '{'.$allBaselines.',"end_construction":"2025-05-01","start_construction":"2025-03-01"}',
                ],
            ],
        );


//            [
//                '{"baseline_start_construction":"2025-04-01","start_development":"2025-05-01"}',
//                FundedMostlyAs::CDEL,
//                [
//                    'developmentOnly' => 'true',
//                    'start_development' => $validDate,
//                    'end_development' => $validDate,
//                ],
//                true,
//                '{"baseline_start_construction":"2025-04-01","end_development":"2025-03-03","start_development":"2025-03-03"}',
//            ]
    }

    protected function getCDELValidationTests(array $validDate): array
    {
        return [
            'CDEL: Missing start_development + end_development' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                ],
                false,
            ],
            'CDEL: Missing end_development' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                    'start_development' => $validDate,
                ],
                false,
            ],
            'CDEL: Missing start_development' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                    'end_development' => $validDate,
                ],
                false,
            ],
            'CDEL: Valid development only' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                ],
                true,
            ],
            'CDEL: Missing start_construction, end_construction, final_delivery' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                ],
                false,
            ],
            'CDEL: Missing end_construction, final_delivery' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'start_construction' => $validDate,
                    ],
                ],
                false,
            ],
            'CDEL: Missing start_construction, final_delivery' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'end_construction' => $validDate,
                    ],
                ],
                false,
            ],
            'CDEL: Missing start_construction, end_construction' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'final_delivery' => $validDate,
                    ],
                ],
                false,
            ],
            'CDEL: Missing end_construction' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'start_construction' => $validDate,
                        'final_delivery' => $validDate,
                    ],
                ],
                false,
            ],
            'CDEL: Missing start_construction' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'end_construction' => $validDate,
                        'final_delivery' => $validDate,
                    ],
                ],
                false,
            ],
            'CDEL: Missing final_delivery' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'start_construction' => $validDate,
                        'end_construction' => $validDate,
                    ],
                ],
                false,
            ],
            'CDEL: Valid non-development' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'start_construction' => $validDate,
                        'end_construction' => $validDate,
                        'final_delivery' => $validDate,
                    ],
                ],
                true,
            ],
        ];
    }

    protected function getRDELValidationTests(array $validDate): array
    {
        return [
            'RDEL: Missing start_development + end_development' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'true',
                ],
                false,
            ],
            'RDEL: Missing end_development' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'true',
                    'start_development' => $validDate,
                ],
                false,
            ],
            'RDEL: Missing start_development' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'true',
                    'end_development' => $validDate,
                ],
                false,
            ],
            'RDEL: Valid development only' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'true',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                ],
                true,
            ],
            'RDEL: Missing start_delivery, end_delivery' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                ],
                false,
            ],
            'RDEL: Missing end_delivery' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'start_delivery' => $validDate,
                    ],
                ],
                false,
            ],
            'RDEL: Missing start_delivery' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'end_delivery' => $validDate,
                    ],
                ],
                false,
            ],
            'RDEL: Valid non-development' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'false',
                    'start_development' => $validDate,
                    'end_development' => $validDate,
                    'nonDevelopmentalMilestones' => [
                        'start_delivery' => $validDate,
                        'end_delivery' => $validDate,
                    ],
                ],
                true,
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(string $initialMilestones, FundedMostlyAs $fundedMostlyAs, array $formData, bool $expectedToBeValid, ?string $expectedOutputMilestones=null): void
    {
        $crstsData = (new CrstsData())
            ->setFundedMostlyAs($fundedMostlyAs);

        $scheme = (new Scheme())
            ->setCrstsData($crstsData);

        $schemeReturn = (new CrstsSchemeReturn())
            ->setScheme($scheme);

        foreach($this->jsonToMilestones($initialMilestones) as $milestone) {
            $schemeReturn->addMilestone($milestone);
        }

        $form = $this->factory->create(MilestoneDatesType::class, $schemeReturn, [
            'cancel_url' => '#',
            'allow_extra_fields' => false,
        ]);

//        $this->debugShowFormStructure($form);

        $form->submit($formData);

        $this->assertNoExtraData($form);

        $this->assertEquals($expectedToBeValid, $form->isValid());

        if ($expectedToBeValid && $expectedOutputMilestones !== null) {
            $this->assertEquals($expectedOutputMilestones, $this->milestonesToJson($schemeReturn->getMilestones()));
        }
    }

    /**
     * Turns an array of milestones into an ordered json representation to allow comparison
     * @param iterable<Milestone> $milestones
     */
    public function milestonesToJson(iterable $milestones): string
    {
        $data = [];

        foreach($milestones as $milestone) {
            $data[$milestone->getType()->value] = $milestone->getDate()?->format('Y-m-d');
        }

        ksort($data);
        return json_encode($data);
    }

    public function jsonToMilestones(string $json): array
    {
        $data = json_decode($json);
        if (!is_object($data)) {
            throw new \RuntimeException('Invalid JSON provided');
        }

        $milestones = [];
        foreach(get_object_vars($data) as $value => $date) {
            $milestones[] = (new Milestone())
                ->setType(MilestoneType::from($value))
                ->setDate(new \DateTime($date));
        }

        return $milestones;
    }
}
