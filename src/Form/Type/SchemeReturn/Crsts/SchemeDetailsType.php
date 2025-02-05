<?php

namespace App\Form\Type\SchemeReturn\Crsts;

use App\Entity\Enum\FundedMostlyAs;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\Type\ReturnBaseType;
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
                'help' => 'forms.crsts.scheme_details.name.help',
                'property_path' => 'schemeFund.scheme.name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'forms.crsts.scheme_details.description.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => 'forms.crsts.scheme_details.description.help',
                'property_path' => 'schemeFund.scheme.description',
            ])
            ->add('risks', TextareaType::class, [
                'label' => 'forms.crsts.scheme_details.risks.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => 'forms.crsts.scheme_details.risks.help',
                'property_path' => 'schemeFund.scheme.risks',
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
            'error_mapping' => [
                'schemeFund.benefitCostRatio' => 'benefitCostRatio',
            ],
        ]);
    }
}
