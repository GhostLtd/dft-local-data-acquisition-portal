<?php

namespace App\Tests\Form\Type\Admin;

use App\Form\Type\Admin\DataExportType;
use App\Tests\DataFixtures\Form\Type\Admin\DataExportTypeFixtures;
use App\Tests\Form\Type\AbstractKernelTypeTest;

class DataExportTypeTest extends AbstractKernelTypeTest
{
    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool->loadFixtures([
            DataExportTypeFixtures::class,
        ]);
    }

    public function dataForm(): array
    {
        return [
            'Valid case 1' => [
                true,
                ['year_and_quarter' => '2024-1']
            ],
            'Valid case 2 (the duplicate)' => [
                true,
                ['year_and_quarter' => '2024-2']
            ],
            'Valid case 3' => [
                true,
                ['year_and_quarter' => '2025-1']
            ],
            'Not submitted (open)' => [
                false,
                ['year_and_quarter' => '2024-3']
            ],
            'Not submitted (closed)' => [
                false,
                ['year_and_quarter' => '2024-4']
            ],
            'Non-existent' => [
                false,
                ['year_and_quarter' => '2025-2']
            ],
        ];
    }

    /**
     * @dataProvider dataForm
     */
    public function testForm(bool $expectedToBeValid, array $formData): void
    {
        $form = $this->formFactory->create(DataExportType::class, null, ['csrf_protection' => false]);
        $form->submit($formData);

        $this->assertEquals($expectedToBeValid, $form->isValid());
    }
}
