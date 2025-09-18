<?php

namespace App\Tests\Form\Type\Admin;

use App\Form\Type\Admin\MaintenanceWarningType;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class MaintenanceWarningTypeTest extends AbstractKernelTypeTest
{
    public function dataForm(): array
    {
        return [
            'Valid maintenance window - afternoon' => [
                true,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '1',
                            'month' => '12',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '2',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '4',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Valid maintenance window - morning' => [
                true,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '15',
                            'month' => '12',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '6',
                            'minute' => '00',
                            'am_or_pm' => 'am'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '8',
                        'minute' => '30',
                        'am_or_pm' => 'am'
                    ]
                ]
            ],
            'Valid maintenance window - overnight' => [
                true,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '31',
                            'month' => '12',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '11',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '2',
                        'minute' => '00',
                        'am_or_pm' => 'am'
                    ]
                ]
            ],
            'Invalid - empty start day' => [
                false,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '',
                            'month' => '12',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '2',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '4',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Invalid - empty start month' => [
                false,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '1',
                            'month' => '',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '2',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '4',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Invalid - empty start year' => [
                false,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '1',
                            'month' => '12',
                            'year' => ''
                        ],
                        'time' => [
                            'hour' => '2',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '4',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Invalid - empty start hour' => [
                false,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '1',
                            'month' => '12',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '4',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Invalid - empty start minute' => [
                false,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '1',
                            'month' => '12',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '2',
                            'minute' => '',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '4',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Invalid - empty end hour' => [
                false,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '1',
                            'month' => '12',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '2',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Invalid - empty end minute' => [
                false,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '1',
                            'month' => '12',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '2',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '4',
                        'minute' => '',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Invalid - invalid day' => [
                false,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '32',
                            'month' => '12',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '2',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '4',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Invalid - invalid month' => [
                false,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '1',
                            'month' => '13',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '2',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '4',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ],
            'Edge case - leap year date' => [
                true,
                [
                    'startDatetime' => [
                        'date' => [
                            'day' => '29',
                            'month' => '2',
                            'year' => '2024'
                        ],
                        'time' => [
                            'hour' => '12',
                            'minute' => '00',
                            'am_or_pm' => 'pm'
                        ]
                    ],
                    'endTime' => [
                        'hour' => '2',
                        'minute' => '00',
                        'am_or_pm' => 'pm'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $form = $this->formFactory->create(MaintenanceWarningType::class, null, [
            'csrf_protection' => false,
            'cancel_url' => '/admin/maintenance'
        ]);

        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());

        if ($expectedToBeValid) {
            $this->assertTrue($form->isSubmitted());
            $data = $form->getData();
            $this->assertIsArray($data);
            $this->assertArrayHasKey('startDatetime', $data);
            $this->assertArrayHasKey('endTime', $data);

            // Both DateTimeType and TimeType transform data into DateTime objects
            $this->assertInstanceOf(\DateTimeInterface::class, $data['startDatetime']);
            $this->assertInstanceOf(\DateTimeInterface::class, $data['endTime']);
        }
    }

}