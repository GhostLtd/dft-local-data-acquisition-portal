<?php

namespace App\Tests\Form\Type;

use App\Form\Type\FilteringDataMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

class FilteringDataMapperTest extends TestCase
{
    private FilteringDataMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new FilteringDataMapper();
    }

    /**
     * @dataProvider filterFormsDataProvider
     */
    public function testFilterForms(array $formNames, array $exclude, array $expectedNames): void
    {
        $forms = array_map($this->createFormMock(...), $formNames);
        $formsIterator = new \ArrayIterator($forms);

        $result = iterator_to_array($this->callFilterForms($formsIterator, $exclude));

        $resultNames = array_map(fn($form) => $form->getName(), $result);
        $this->assertEquals($expectedNames, array_values($resultNames));
    }

    public function filterFormsDataProvider(): array
    {
        return [
            'no exclusions returns all forms' => [
                'formNames' => ['field1', 'field2', 'field3'],
                'exclude' => [],
                'expectedNames' => ['field1', 'field2', 'field3'],
            ],
            'excludes single specified form' => [
                'formNames' => ['field1', 'field2', 'field3'],
                'exclude' => ['field2'],
                'expectedNames' => ['field1', 'field3'],
            ],
            'excludes multiple specified forms' => [
                'formNames' => ['field1', 'field2', 'field3', 'field4'],
                'exclude' => ['field1', 'field3'],
                'expectedNames' => ['field2', 'field4'],
            ],
            'returns empty when all forms excluded' => [
                'formNames' => ['field1', 'field2'],
                'exclude' => ['field1', 'field2'],
                'expectedNames' => [],
            ],
            'handles empty forms collection' => [
                'formNames' => [],
                'exclude' => ['field1'],
                'expectedNames' => [],
            ],
            'ignores non-existent exclusions' => [
                'formNames' => ['field1', 'field2'],
                'exclude' => ['nonexistent', 'field2', 'another_nonexistent'],
                'expectedNames' => ['field1'],
            ],
            'preserves form order' => [
                'formNames' => ['field1', 'field2', 'field3', 'field4'],
                'exclude' => ['field2'],
                'expectedNames' => ['field1', 'field3', 'field4'],
            ],
        ];
    }

    private function createFormMock(string $name): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('getName')->willReturn($name);
        return $form;
    }

    private function callFilterForms(\Traversable $forms, array $exclude = []): \Generator
    {
        $reflection = new \ReflectionClass($this->mapper);
        $method = $reflection->getMethod('filterForms');
        return $method->invoke($this->mapper, $forms, $exclude);
    }
}
