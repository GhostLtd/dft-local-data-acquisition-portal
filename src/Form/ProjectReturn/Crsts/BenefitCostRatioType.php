<?php

namespace App\Form\ProjectReturn\Crsts;

use App\Entity\Enum\BenefitCostRatioType as BenefitCostRatioTypeEnum;
use App\Entity\ProjectFund\BenefitCostRatio;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BenefitCostRatioType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this)
            ->add('type', ChoiceType::class, [
                'label' => 'forms.crsts.benefit_cost_ratio.type.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.crsts.benefit_cost_ratio.type.help',
                'choices' => BenefitCostRatioTypeEnum::cases(),
                'choice_label' => fn(BenefitCostRatioTypeEnum $choice) => "enum.benefit_cost_ratio_type.{$choice->value}",
                'choice_value' => fn(?BenefitCostRatioTypeEnum $choice) => $choice?->value,
                'property_path' => 'type',
                'choice_options' => function(?BenefitCostRatioTypeEnum $choice) {
                    return $choice === BenefitCostRatioTypeEnum::VALUE ?
                        ['conditional_form_name' => 'value'] :
                        [];
                },
            ])
            // We can't use a DecimalType here, because its validation triggers even when we've specified
            // N/A or TBC for "type" and this field is hidden (see
            ->add('value', InputType::class, [
                'label' => 'forms.crsts.benefit_cost_ratio.value.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => 'forms.crsts.benefit_cost_ratio.value.help',
                'property_path' => 'value',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BenefitCostRatio::class,
        ]);
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        if (!$viewData instanceof BenefitCostRatio) {
            throw new UnexpectedTypeException($viewData, BenefitCostRatio::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $type = $viewData->getType();

        $forms['type']->setData($type);
        $forms['value']->setData($type === BenefitCostRatioTypeEnum::VALUE ? $viewData->getValue() : null);
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof BenefitCostRatio) {
            throw new UnexpectedTypeException($viewData, BenefitCostRatio::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $type = $forms['type']->getData();

        $value = $type === BenefitCostRatioTypeEnum::VALUE ?
            $forms['value']->getData() :
            null;

        $viewData->setType($type);
        $viewData->setValue($value);
    }
}
