<?php

namespace App\Form\Type\FundReturn\Crsts;

use App\Entity\FundReturn\CrstsFundReturn;
use App\Form\Type\BaseButtonsFormType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalAndRdelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('localContribution', TextareaType::class, [
                'label' => 'forms.crsts.local_and_rdel.local_contribution.label',
                'help' => 'forms.crsts.local_and_rdel.local_contribution.help',
                'label_attr' => ['class' => 'govuk-label--s']
            ])
            ->add('resourceFunding', TextareaType::class, [
                'label' => 'forms.crsts.local_and_rdel.resource_funding.label',
                'help' => 'forms.crsts.local_and_rdel.resource_funding.help',
                'help_html' => 'markdown',
                'label_attr' => ['class' => 'govuk-label--s']
            ])
        ;
    }

    public function getParent(): string
    {
        return BaseButtonsFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CrstsFundReturn::class,
            'validation_groups' => ['local_and_rdel'],
        ]);
    }
}
