<?php

namespace App\Form\ProjectReturn\Crsts;

use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Form\ReturnBaseType;
use Ghost\GovUkFrontendBundle\Form\Type\DecimalType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OverallFundingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('totalCost', DecimalType::class, [
                'precision' => 10,
                'scale' => 2,
            ])
            ->add('agreedFunding', DecimalType::class, [
                'precision' => 10,
                'scale' => 2,
            ])
        ;
    }

    public function getParent(): string
    {
        return ReturnBaseType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', CrstsProjectReturn::class);
    }
}
