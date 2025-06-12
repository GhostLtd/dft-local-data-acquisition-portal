<?php

namespace App\Form\Type\FundReturn\Crsts;

use App\Entity\Enum\ExpenseType;
use App\Entity\ExpensesContainerInterface;
use App\Form\Type\BaseButtonsFormType;
use App\Utility\ExpensesTableHelper;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
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
            $cellOptions = $cell->getOptions();

            $rowsToSum = $cell->getAttribute('total_rows_to_sum');
            if (is_array($rowsToSum)) {
                $rowsToSum = join(',', array_map(fn(ExpenseType $expenseType) => $expenseType->value, $rowsToSum));
            }

            $attributes = array_filter([
                'data-col' => $cell->getAttribute('col_key'),
                'data-row' => $cell->getAttribute('row_key'),
                'data-total-sum-rows-in-column' => $rowsToSum,
                'data-total-sum-entire-row' => $cell->getAttribute('is_row_total') ? '1' : null,
            ], fn(mixed $value): bool => $value !== null);

            $builder->add($cellOptions['key'], InputType::class, [
                'label' => $cellOptions['text'],
                'disabled' => $cellOptions['disabled'] ?? null,
                'label_attr' => ['class' => 'govuk-visually-hidden'],
                'attr' => $attributes,
            ]);
        }

        if ($options['comments_enabled']) {
            $builder->add('comments', TextareaType::class, [
                'label' => "forms.crsts.expenses.comments.label",
                'label_attr' => ['class' => 'govuk-visually-hidden'],
                'help' => "forms.crsts.expenses.comments.help",
                'attr' => ['class' => 'govuk-!-margin-bottom-0'],
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
            ->setDefault('comments_enabled', true)
            ->setAllowedTypes('comments_enabled', ['boolean'])
            ->setDefault('validation_groups', ['expenses'])
            ->setDefault('data_class', ExpensesContainerInterface::class);
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }
}
