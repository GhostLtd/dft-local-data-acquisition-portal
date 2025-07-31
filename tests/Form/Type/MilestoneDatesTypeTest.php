<?php

namespace App\Tests\Form\Type;

use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\Milestone;
use App\Entity\Scheme;
use App\Entity\SchemeData\CrstsData;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\SchemeReturn\Crsts\MilestoneDatesType;

class MilestoneDatesTypeTest extends AbstractTypeTest
{
    public function dataForm(): \Generator
    {
        $validDate = ['year' => '2025', 'month' => '3', 'day' => '3'];

        $cases = array_merge(
            $this->getCDELValidationTestCases($validDate),
            $this->getRDELValidationTestCases($validDate),
            $this->getDataTransformTestCases(),
        );

        // All test cases... once with milestones disabled, and then once with milestones enabled
        foreach([false, true] as $milestonesEnabled) {
            foreach($cases as $case) {
                yield [$milestonesEnabled, ...$case];
            }
        }
    }

    protected function getCDELValidationTestCases(array $validDate): array
    {
        return [
            'CDEL: Validation - Missing start_development + end_development' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                ],
                false,
            ],
            'CDEL: Validation - Missing end_development' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                    'group_start_development' => ['start_development' => $validDate],
                ],
                false,
            ],
            'CDEL: Validation - Missing start_development' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                    'group_end_development' => ['end_development' => $validDate],
                ],
                false,
            ],
            'CDEL: Validation - Valid development only' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                ],
                true,
            ],
            'CDEL: Validation - Missing start_construction, end_construction, final_delivery' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                ],
                false,
            ],
            'CDEL: Validation - Missing end_construction, final_delivery' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_start_construction' => ['start_construction' => $validDate],
                    ],
                ],
                false,
            ],
            'CDEL: Validation - Missing start_construction, final_delivery' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_end_construction' => ['end_construction' => $validDate],
                    ],
                ],
                false,
            ],
            'CDEL: Validation - Missing start_construction, end_construction' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_final_delivery' => ['final_delivery' => $validDate],
                    ],
                ],
                false,
            ],
            'CDEL: Validation - Missing end_construction' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_start_construction' => ['start_construction' => $validDate],
                        'group_final_delivery' => ['final_delivery' => $validDate],
                    ],
                ],
                false,
            ],
            'CDEL: Validation - Missing start_construction' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_end_construction' => ['end_construction' => $validDate],
                        'group_final_delivery' => ['final_delivery' => $validDate],
                    ],
                ],
                false,
            ],
            'CDEL: Validation - Missing final_delivery' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_start_construction' => ['start_construction' => $validDate],
                        'group_end_construction' => ['end_construction' => $validDate],
                    ],
                ],
                false,
            ],
            'CDEL: Validation - Valid non-development' => [
                '{}',
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_start_construction' => ['start_construction' => $validDate],
                        'group_end_construction' => ['end_construction' => $validDate],
                        'group_final_delivery' => ['final_delivery' => $validDate],
                    ],
                ],
                true,
            ],
        ];
    }

    protected function getRDELValidationTestCases(array $validDate): array
    {
        return [
            'RDEL: Validation - Missing start_development + end_development' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'true',
                ],
                false,
            ],
            'RDEL: Validation - Missing end_development' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'true',
                    'group_start_development' => ['start_development' => $validDate],
                ],
                false,
            ],
            'RDEL: Validation - Missing start_development' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'true',
                    'group_end_development' => ['end_development' => $validDate],
                ],
                false,
            ],
            'RDEL: Validation - Valid development only' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'true',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                ],
                true,
            ],
            'RDEL: Validation - Missing start_delivery, end_delivery' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                ],
                false,
            ],
            'RDEL: Validation - Missing end_delivery' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_start_delivery' => ['start_delivery' => $validDate],
                    ],
                ],
                false,
            ],
            'RDEL: Validation - Missing start_delivery' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_end_delivery' => ['end_delivery' => $validDate],
                    ],
                ],
                false,
            ],
            'RDEL: Validation - Valid non-development' => [
                '{}',
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => $validDate],
                    'group_end_development' => ['end_development' => $validDate],
                    'nonDevelopmentalMilestones' => [
                        'group_start_delivery' => ['start_delivery' => $validDate],
                        'group_end_delivery' => ['end_delivery' => $validDate],
                    ],
                ],
                true,
            ],
        ];
    }

    protected function getDataTransformTestCases(): array
    {
        $allBaselines = '"baseline_end_construction":"2025-04-01","baseline_end_delivery":"2025-04-01","baseline_end_development":"2025-04-01","baseline_final_delivery":"2025-04-01","baseline_start_construction":"2025-04-01","baseline_start_delivery":"2025-04-01","baseline_start_development":"2025-04-01"';
        $allMilestones = '{' . $allBaselines . ',"end_construction":"2025-04-01","end_delivery":"2025-04-01","end_development":"2025-04-01","final_delivery":"2025-04-01","start_construction":"2025-04-01","start_delivery":"2025-04-01","start_development":"2025-04-01"}';

        return [
            'CDEL: Data transforms - development-only (baselines, start/end development remain)' => [
                $allMilestones,
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                    'group_start_development' => ['start_development' => ['year' => '2025', 'month' => '3', 'day' => '3']],
                    'group_end_development' => ['end_development' => ['year' => '2025', 'month' => '5', 'day' => '1']],
                ],
                true,
                '{' . $allBaselines . ',"end_development":"2025-05-01","start_development":"2025-03-03"}',
            ],
            'CDEL: Data transforms - not development-only (baselines, start/end development, start/end construction, final delivery remain)' => [
                $allMilestones,
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => ['year' => '2025', 'month' => '3', 'day' => '3']],
                    'group_end_development' => ['end_development' => ['year' => '2025', 'month' => '5', 'day' => '1']],
                    'nonDevelopmentalMilestones' => [
                        'group_start_construction' => ['start_construction' => ['year' => '2025', 'month' => '6', 'day' => '6']],
                        'group_end_construction' => ['end_construction' => ['year' => '2025', 'month' => '7', 'day' => '7']],
                        'group_final_delivery' => ['final_delivery' => ['year' => '2025', 'month' => '8', 'day' => '8']],
                    ],
                ],
                true,
                '{' . $allBaselines . ',"end_construction":"2025-07-07","end_development":"2025-05-01","final_delivery":"2025-08-08","start_construction":"2025-06-06","start_development":"2025-03-03"}',
            ],
            'RDEL: Data transforms - development-only (baselines, start/end development remain)' => [
                $allMilestones,
                FundedMostlyAs::CDEL,
                [
                    'developmentOnly' => 'true',
                    'group_start_development' => ['start_development' => ['year' => '2025', 'month' => '3', 'day' => '3']],
                    'group_end_development' => ['end_development' => ['year' => '2025', 'month' => '5', 'day' => '1']],
                ],
                true,
                '{' . $allBaselines . ',"end_development":"2025-05-01","start_development":"2025-03-03"}',
            ],
            'RDEL: Data transforms - not development-only (baselines, start/end development, start/end delivery remain)' => [
                $allMilestones,
                FundedMostlyAs::RDEL,
                [
                    'developmentOnly' => 'false',
                    'group_start_development' => ['start_development' => ['year' => '2025', 'month' => '3', 'day' => '3']],
                    'group_end_development' => ['end_development' => ['year' => '2025', 'month' => '5', 'day' => '1']],
                    'nonDevelopmentalMilestones' => [
                        'group_start_delivery' => ['start_delivery' => ['year' => '2025', 'month' => '6', 'day' => '6']],
                        'group_end_delivery' => ['end_delivery' => ['year' => '2025', 'month' => '7', 'day' => '7']],
                    ],
                ],
                true,
                '{' . $allBaselines . ',"end_delivery":"2025-07-07","end_development":"2025-05-01","start_delivery":"2025-06-06","start_development":"2025-03-03"}',
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $milestonesEnabled, string $initialMilestones, FundedMostlyAs $fundedMostlyAs, array $formData, bool $expectedToBeValid, ?string $expectedOutputMilestones = null): void
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
            'milestones_enabled' => $milestonesEnabled,
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
