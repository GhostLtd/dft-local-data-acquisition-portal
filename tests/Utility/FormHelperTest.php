<?php

namespace App\Tests\Utility;

use App\Utility\FormHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

class FormHelperTest extends TestCase
{
    /**
     * @dataProvider buttonClickedDataProvider
     */
    public function testWhichButtonClicked(
        array $buttonConfigs,
        null|string|array $amongstTheseButtonNames,
        ?string $expectedResult
    ): void {
        $form = $this->createFormWithButtons($buttonConfigs);

        $result = FormHelper::whichButtonClicked($form, $amongstTheseButtonNames);

        $this->assertSame($expectedResult, $result);
    }

    public function buttonClickedDataProvider(): array
    {
        return [
            'no buttons returns null' => [
                'buttonConfigs' => [],
                'amongstTheseButtonNames' => null,
                'expectedResult' => null,
            ],
            'clicked button is returned' => [
                'buttonConfigs' => [
                    ['name' => 'save', 'clicked' => true],
                    ['name' => 'cancel', 'clicked' => false],
                ],
                'amongstTheseButtonNames' => null,
                'expectedResult' => 'save',
            ],
            'first clicked button is returned when multiple clicked' => [
                'buttonConfigs' => [
                    ['name' => 'save', 'clicked' => true],
                    ['name' => 'submit', 'clicked' => true],
                ],
                'amongstTheseButtonNames' => null,
                'expectedResult' => 'save',
            ],
            'returns null when no buttons clicked' => [
                'buttonConfigs' => [
                    ['name' => 'save', 'clicked' => false],
                    ['name' => 'cancel', 'clicked' => false],
                ],
                'amongstTheseButtonNames' => null,
                'expectedResult' => null,
            ],
            'filters by single button name string' => [
                'buttonConfigs' => [
                    ['name' => 'save', 'clicked' => true],
                    ['name' => 'cancel', 'clicked' => true],
                ],
                'amongstTheseButtonNames' => 'cancel',
                'expectedResult' => 'cancel',
            ],
            'filters by array of button names' => [
                'buttonConfigs' => [
                    ['name' => 'save', 'clicked' => true],
                    ['name' => 'cancel', 'clicked' => false],
                    ['name' => 'submit', 'clicked' => true],
                ],
                'amongstTheseButtonNames' => ['cancel', 'submit'],
                'expectedResult' => 'submit',
            ],
            'returns null when clicked button not in filter list' => [
                'buttonConfigs' => [
                    ['name' => 'save', 'clicked' => true],
                    ['name' => 'cancel', 'clicked' => false],
                ],
                'amongstTheseButtonNames' => ['cancel', 'submit'],
                'expectedResult' => null,
            ],
        ];
    }

    private function createFormWithButtons(array $buttonConfigs): FormInterface
    {
        $formFactory = Forms::createFormFactory();

        $form = $formFactory->createBuilder()
            ->add('buttons', FormType::class)
            ->getForm();

        $buttonsForm = $form->get('buttons');

        foreach ($buttonConfigs as $config) {
            $buttonsForm->add($config['name'], SubmitType::class);
        }

        // Simulate button clicks after all buttons are added
        foreach ($buttonConfigs as $config) {
            if ($config['clicked']) {
                $buttonsForm->get($config['name'])->submit('');
            }
        }

        return $form;
    }
}
