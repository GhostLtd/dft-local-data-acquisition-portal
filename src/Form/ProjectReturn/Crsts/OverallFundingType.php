<?php

namespace App\Form\ProjectReturn\Crsts;

use App\Entity\ProjectReturn\CrstsProjectReturn;
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
                'label_attr' => ['class' => 'govuk-label--s']
            ])
            ->add('agreedFunding', InputType::class, [
                'label' => 'forms.crsts.overall_funding.agreed_funding.label',
                'label_attr' => ['class' => 'govuk-label--s']
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
            'data_class' => CrstsProjectReturn::class,
            'validation_groups' => ['overall_funding'],
        ]);
    }
}
