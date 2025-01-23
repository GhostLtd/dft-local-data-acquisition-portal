<?php

namespace App\Form\SchemeReturn\Crsts;

use App\Entity\Enum\FundedMostlyAs;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchemeDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', InputType::class, [
                'label' => 'forms.crsts.scheme_details.name.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'schemeFund.scheme.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'forms.crsts.scheme_details.description.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'schemeFund.scheme.description',
            ])
            ->add('schemeIdentifier', InputType::class, [
                'label' => 'forms.crsts.scheme_details.scheme_identifier.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'property_path' => 'schemeFund.scheme.schemeIdentifier',
            ])
            ->add('previouslyTcf', BooleanChoiceType::class, [
                'label' => 'forms.crsts.scheme_details.previously_tcf.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.crsts.scheme_details.previously_tcf.help',
                'property_path' => 'schemeFund.previouslyTcf',
            ])
            ->add('benefitCostRatio', BenefitCostRatioType::class, [
                'label' => false,
                'property_path' => 'schemeFund.benefitCostRatio',
            ])
            ->add('fundedMostlyAs', ChoiceType::class, [
                'label' => 'forms.crsts.scheme_details.funded_mostly_as.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'forms.crsts.scheme_details.funded_mostly_as.help',
                'choices' => FundedMostlyAs::cases(),
                'choice_label' => fn(FundedMostlyAs $choice) => "enum.funded_mostly_as.{$choice->value}",
                'choice_value' => fn(FundedMostlyAs $choice) => $choice->value,
                'property_path' => 'schemeFund.fundedMostlyAs',
            ])
        ;
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CrstsSchemeReturn::class,
            'validation_groups' => ['scheme_details'],
        ]);
    }
}
