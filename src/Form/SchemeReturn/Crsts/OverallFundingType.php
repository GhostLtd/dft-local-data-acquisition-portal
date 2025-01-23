<?php

namespace App\Form\SchemeReturn\Crsts;

use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Form\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OverallFundingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('totalCost', InputType::class, [
                'label' => 'forms.crsts.overall_funding.total_cost.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'prefix' => '£',
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('agreedFunding', InputType::class, [
                'label' => 'forms.crsts.overall_funding.agreed_funding.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'prefix' => '£',
                'attr' => ['class' => 'govuk-input--width-10'],
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
            'validation_groups' => ['overall_funding'],
        ]);
    }
}
