<?php

namespace App\Form\FundReturn\Crsts;

use App\Entity\Enum\ExpenseType;
use App\Entity\ExpensesContainerInterface;
use App\Form\ReturnBaseType;
use App\Utility\ExpensesTableHelper;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExpensesType extends AbstractType
{
    public function __construct(
        protected ExpensesDataMapper $expensesDataMapper
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ExpensesTableHelper $tableHelper */
        $tableHelper = $options['expenses_table_helper'];

        $this->expensesDataMapper->setTableHelper($tableHelper);
        $builder->setDataMapper($this->expensesDataMapper);

        foreach($tableHelper->getAllCells() as $cell) {
            $options = $cell->getOptions();

            $rowsToSum = $cell->getAttribute('total_rows_to_sum');
            if (is_array($rowsToSum)) {
                $rowsToSum = join(',', array_map(fn(ExpenseType $expenseType) => $expenseType->value, $rowsToSum));
            }

            $attributes = array_filter([
                'data-col' => $cell->getAttribute('sub_division'),
                'data-row' => $cell->getAttribute('row_slug'),
                'data-total-sum-rows-in-column' => $rowsToSum,
                'data-total-sum-entire-row' => $cell->getAttribute('is_row_total') ? '1' : null,
            ], fn(mixed $value): bool => $value !== null);

            $builder->add($options['key'], InputType::class, [
                'label' => $options['text'],
                'disabled' => $options['disabled'] ?? null,
                'label_attr' => ['class' => 'govuk-visually-hidden'],
                'translation_domain' => false,
                'attr' => $attributes,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('expenses_table_helper')
            ->setAllowedTypes('expenses_table_helper', ExpensesTableHelper::class)
            ->setDefault('attr', [
                'data-auto-total' => '1',
                'data-auto-commas' => '1',
                'class' => 'expenses',
            ])
            ->setDefault('validation_groups', ['expenses'])
            ->setDefault('data_class', ExpensesContainerInterface::class);
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }
}
